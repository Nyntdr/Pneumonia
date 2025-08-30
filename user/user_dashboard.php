<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login/login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pneumonia";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to validate if image is likely a chest X-ray
function is_chest_xray($file_path) {
    error_log("Validating image: $file_path");
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
        error_log("GD not available, skipping validation");
        return true;
    }
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($ext == 'jpeg' || $ext == 'jpg' || $ext == 'png') {
        $img = $ext == 'png' ? @imagecreatefrompng($file_path) : @imagecreatefromjpeg($file_path);
    } else {
        return false;
    }
    if (!$img) {
        return false;
    }
    $width = imagesx($img);
    $height = imagesy($img);
    if ($width < 100 || $height < 100) {
        imagedestroy($img);
        return false;
    }
    // Log aspect ratio but don't reject
    if ($height < $width * 0.8) {
        error_log("Warning: Unusual aspect ratio (width=$width, height=$height)");
    }
    $intensities = [];
    $color_variations = [];
    $lung_left = [];
    $lung_right = [];
    $center = [];
    $histogram = array_fill(0, 256, 0);
    for ($x = 0; $x < $width; $x += 4) {
        for ($y = 0; $y < $height; $y += 4) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $gray = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
            $intensities[] = $gray;
            $color_variations[] = max($r, $g, $b) - min($r, $g, $b);
            $histogram[$gray]++;
            if ($y < $height * 0.6 && $x < $width * 0.35) {
                $lung_left[] = $gray;
            } elseif ($y < $height * 0.6 && $x > $width * 0.65) {
                $lung_right[] = $gray;
            }
            if ($x > $width * 0.35 && $x < $width * 0.65 && $y > $height * 0.2 && $y < $height * 0.8) {
                $center[] = $gray;
            }
        }
    }
    $variance = count($intensities) > 0 ? array_sum(array_map(function($x) use ($intensities) {
        return pow($x - (array_sum($intensities) / count($intensities)), 2);
    }, $intensities)) / count($intensities) : 0;
    $std_dev = sqrt($variance);
    $edge_score = 0;
    for ($x = 1; $x < $width - 1; $x += 4) {
        for ($y = 1; $y < $height - 1; $y += 4) {
            $p1 = imagecolorat($img, $x-1, $y-1) & 0xFF;
            $p2 = imagecolorat($img, $x+1, $y+1) & 0xFF;
            $edge_score += abs($p1 - $p2);
        }
    }
    $edge_score /= ($width * $height / 16);
    $avg_color_variation = count($color_variations) > 0 ? array_sum($color_variations) / count($color_variations) / 255 : 1;
    $lung_left_avg = count($lung_left) > 0 ? array_sum($lung_left) / count($lung_left) : 0;
    $lung_right_avg = count($lung_right) > 0 ? array_sum($lung_right) / count($lung_right) : 0;
    $center_avg = count($center) > 0 ? array_sum($center) / count($center) : 0;
    $lung_field_valid = $lung_left_avg < 120 && $lung_right_avg < 120 && abs($lung_left_avg - $lung_right_avg) < 20 && $center_avg > $lung_left_avg - 20 && $center_avg > 100 && $lung_left_avg > 50;
    $dark_pixels = array_sum(array_slice($histogram, 0, 100)) / array_sum($histogram);
    $bright_pixels = array_sum(array_slice($histogram, 150, 256)) / array_sum($histogram);
    $histogram_valid = $dark_pixels > 0.20 && $dark_pixels < 0.90 && $bright_pixels > 0.10;
    imagedestroy($img);
    if ($avg_color_variation > 0.05) {
        error_log("Image rejected: Significant color variation detected ($avg_color_variation)");
        return false;
    }
    if (!$lung_field_valid) {
        error_log("Image rejected: No valid lung fields detected (left_avg=$lung_left_avg, right_avg=$lung_right_avg, center_avg=$center_avg)");
        return false;
    }
    if (!$histogram_valid) {
        error_log("Image rejected: Invalid intensity distribution (dark_pixels=$dark_pixels, bright_pixels=$bright_pixels)");
        return false;
    }
    $result = $std_dev < 65 && $edge_score > 10;
    error_log("Validation result: std_dev=$std_dev, edge_score=$edge_score, avg_color_variation=$avg_color_variation, lung_field_valid=$lung_field_valid, histogram_valid=$histogram_valid, result=$result");
    return $result;
}

// Handle file upload
$error = null;
$prediction = null;
$confidence = null;
$image_file = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xray_image'])) {
    error_log("Form submitted with file: " . print_r($_FILES['xray_image'], true));
    $file = $_FILES['xray_image'];
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload failed: " . $file['error'];
    } elseif (!in_array($file['type'], $allowed_types)) {
        $error = "Only JPEG, JPG, or PNG files allowed.";
    } elseif ($file['size'] > $max_size) {
        $error = "File size exceeds 5MB.";
    } else {
        if (!is_chest_xray($file['tmp_name'])) {
            $error = "Only chest X-ray images allowed.";
        } else {
            $upload_dir = "../Uploads/";
            $file_name = uniqid() . '_' . basename($file['name']);
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                error_log("File saved to: $file_path");
                $url = 'http://localhost:5000/api/predict';
                $cfile = new CURLFile(realpath($file_path), $file['type'], 'file');
                $post = ['file' => $cfile];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                if ($response === false) {
                    error_log("Curl error: " . curl_error($ch));
                }
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                error_log("Curl response: $response, HTTP code: $http_code");

                if ($http_code === 200) {
                    $result = json_decode($response, true);
                    if (isset($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $prediction = $result['prediction'];
                        $confidence = $result['confidence'];
                        $image_file = $file_path;
                        $stmt = $conn->prepare("INSERT INTO scans (user_id, image_path, upload_date, outcome, Confidence) VALUES (?, ?, NOW(), ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $file_path, $prediction, $confidence]);
                    }
                } else {
                    $error = "Failed to get prediction from server.";
                }
            } else {
                $error = "Failed to save uploaded file: " . print_r(error_get_last(), true);
            }
        }
    }
}

// Load scan history
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, image_path, upload_date, outcome FROM scans WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->execute([$user_id]);
$scans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Additional content for popup based on prediction
$additional_content = '';
if (isset($prediction)) {
    if (strtolower($prediction) === 'pneumonia') {
        $additional_content = '
        <div class="precautions">
            <h3>Precautions to follow:</h3>
            <ul>
                <li>Follow your doctor\'s advice and take prescribed antibiotics or antivirals.</li>
                <li>Get plenty of rest to help your body recover.</li>
                <li>Drink lots of fluids to stay hydrated and loosen mucus.</li>
                <li>Use a humidifier or take steam baths to ease breathing.</li>
                <li>Avoid smoking and exposure to air pollutants or irritants.</li>
                <li>Monitor your symptoms and seek immediate medical help if they worsen.</li>
            </ul>
            <h3>Recommended Hospitals in Nepal for Pneumonia Treatment:</h3>
            <ul>
                <li>Nepal Mediciti Hospital, Nakhu, Lalitpur - <a href="tel:+977014217766">+977-01-4217766</a></li>
                <li>Grande International Hospital, Dhapasi, Kathmandu - <a href="tel:+977014379100">+977-01-4379100</a></li>
                <li>HAMS Hospital, Dhumbarahi, Kathmandu - <a href="tel:+977014375055">+977-01-4375055</a></li>
                <li>B&B Hospital, Gwarko, Lalitpur - <a href="tel:+977015532095">+977-01-5532095</a></li>
            </ul>
        </div>
        ';
    } else {
        $additional_content = '
        <div class="healthy">
            <h3>You Seem Healthy!</h3>
            <p>Here are some tips to stay pneumonia-free if you want to know:</p>
            <ul>
                <li>Get vaccinated against flu and pneumococcus to boost immunity.</li>
                <li>Practice good hygiene: wash hands regularly with soap and water.</li>
                <li>Maintain a healthy lifestyle with a balanced diet, regular exercise, and adequate sleep.</li>
                <li>Avoid smoking and exposure to secondhand smoke to protect your lungs.</li>
                <li>Stay away from people with respiratory infections to reduce risk.</li>
            </ul>
        </div>
        ';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Pneumonia Detection</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/common.css">
    <link rel="stylesheet" href="../style/user.css">
    <style>
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            z-index: 1001;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .popup img {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 20px;
        }
        .prediction, .confidence, .precautions, .healthy {
            margin: 10px 0;
        }
        .prediction.pneumonia {
            color: #d32f2f;
        }
        .prediction.normal {
            color: #388e3c;
        }
        .precautions ul, .healthy ul {
            list-style-type: disc;
            margin-left: 20px;
        }
        .precautions h3, .healthy h3 {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">Pneumonia Detection System</div>
        <div>
            <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../login/logout.php" onclick="return confirmLogout()">Logout</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="left-column">
            <div class="info-box">
                <h2>About Pneumonia</h2>
                <div class="info-content">
                    <h3>Definition:</h3>
                    <p>Pneumonia is an infection that inflames the air sacs in one or both lungs, which may fill with fluid.</p>
                    <h3>Common Symptoms:</h3>
                    <ul>
                        <li>Cough with phlegm or pus</li>
                        <li>Fever, chills, and sweating</li>
                        <li>Shortness of breath</li>
                        <li>Chest pain when breathing</li>
                        <li>Fatigue and loss of appetite</li>
                    </ul>
                    <h3>X-Ray Indicators:</h3>
                    <ul>
                        <li>White spots in the lungs (infiltrates)</li>
                        <li>Dense patches indicating fluid</li>
                        <li>Blurred edges around the lungs</li>
                    </ul>
                </div>
                <div class="info-images">
                    <div class="image-comparison">
                        <img src="../images/pneu.png" alt="Normal Chest X-Ray">
                        <p class="image-caption">Normal</p>
                    </div>
                    <div class="image-comparison">
                        <img src="../images/xxray.jpeg" alt="Pneumonia Chest X-Ray">
                        <p class="image-caption">Pneumonia X-Ray</p>
                    </div>
                </div>
            </div>

            <div class="upload-box">
                <h2>Upload Chest X-Ray</h2>
                <form id="uploadForm" method="post" enctype="multipart/form-data">
                    <div id="uploadError" class="error" style="display: none;"></div>
                    <div class="upload-area" id="dropZone">
                        <input type="file" id="xray_image" name="xray_image" accept="image/jpeg,image/png" required>
                        <label for="xray_image" class="upload-label">
                            <img src="../icons/upload.png" alt="Upload Icon">
                            <span>Click to browse or drag & drop X-Ray image</span>
                            <span class="file-requirements">(JPEG, JPG, PNG, max 5MB)</span>
                        </label>
                    </div>
                    <div id="imagePreviewContainer">
                        <div class="preview-header">
                            <h3>Image Preview</h3>
                            <button type="button" id="clearPreview">×</button>
                        </div>
                        <img id="imagePreview">
                        <div class="preview-footer">
                            <span id="fileName"></span>
                            <span id="fileSize"></span>
                        </div>
                    </div>
                    <button type="submit" class="upload-btn" id="uploadBtn">
                        <span class="btn-text">Analyze X-Ray</span>
                        <span class="spinner" id="spinner"></span>
                    </button>
                </form>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif (isset($prediction) && isset($image_file)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const popup = document.createElement('div');
                            popup.className = 'popup';
                            popup.innerHTML = `
                                <span class="close-btn" onclick="this.parentElement.style.display='none';document.querySelector('.overlay').style.display='none'">&times;</span>
                                <img src="<?php echo htmlspecialchars($image_file); ?>" alt="Uploaded X-Ray">
                                <div class="prediction <?php echo strtolower(htmlspecialchars($prediction)); ?>">
                                    Prediction: <?php echo htmlspecialchars($prediction); ?>
                                </div>
                                <div class="confidence">
                                    Confidence: <?php echo htmlspecialchars($confidence); ?>
                                </div>
                                <?php echo $additional_content; ?>
                            `;
                            document.body.appendChild(popup);

                            const overlay = document.createElement('div');
                            overlay.className = 'overlay';
                            document.body.appendChild(overlay);

                            popup.style.display = 'block';
                            overlay.style.display = 'block';
                        });
                    </script>
                <?php endif; ?>
            </div>
        </div>

        <div class="history-box">
            <div class="history-header">
                <h2>Your X-Ray History</h2>
                <div class="history-stats">
                    <span><?php echo count($scans); ?> scans</span>
                </div>
            </div>
            <div class="image-grid" id="historyGrid">
                <?php foreach ($scans as $scan): 
                    $uploadDate = date("M d, Y H:i", strtotime($scan['upload_date']));
                    $outcome_raw = $scan['outcome'] ?? 'Pending';
                    $outcome_clean = trim(strtolower($outcome_raw));
                    $outcome_display = ucfirst($outcome_clean);
                ?>
                    <div class="image-card">
                        <div class="card-header">
                            <span class="upload-date"><?php echo htmlspecialchars($uploadDate); ?></span>
                            <span class="scan-outcome" data-outcome="<?php echo htmlspecialchars($outcome_clean); ?>">
                                <?php echo htmlspecialchars($outcome_display); ?>
                            </span>
                        </div>
                        <img src="<?php echo htmlspecialchars($scan['image_path']); ?>" alt="X-Ray Scan" loading="lazy">
                        <div class="card-footer">
                            <form action="analyze.php" method="get" style="margin: 0; width: 100%;">
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars(basename($scan['image_path'])); ?>">
                                <button type="submit" class="view-btn" title="View details">View</button>
                            </form>
                            <form action="delete_image.php" method="post" style="margin: 0; width: 100%;">
                                <input type="hidden" name="image_name" value="<?php echo htmlspecialchars(basename($scan['image_path'])); ?>">
                                <input type="hidden" name="scan_id" value="<?php echo htmlspecialchars($scan['id']); ?>">
                                <button type="submit" class="delete-btn" title="Delete scan" onclick="return confirm('Are you sure you want to delete this scan?')">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($scans)): ?>
                    <div class="empty-history">
                        <img src="../icons/empty.png" alt="No scans yet">
                        <p>No X-Ray scans uploaded yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }

        function isGrayscale(file, callback) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const maxSize = 100; // Resize to 100x100 for performance
                canvas.width = maxSize;
                canvas.height = maxSize;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, maxSize, maxSize);
                const imageData = ctx.getImageData(0, 0, maxSize, maxSize).data;
                let colorVariations = [];
                for (let i = 0; i < imageData.length; i += 40) { // Sample every 10th pixel
                    const r = imageData[i];
                    const g = imageData[i + 1];
                    const b = imageData[i + 2];
                    colorVariations.push(Math.max(r, g, b) - Math.min(r, g, b));
                }
                const avgColorVariation = colorVariations.length > 0 
                    ? colorVariations.reduce((a, b) => a + b, 0) / colorVariations.length / 255 
                    : 1;
                callback({
                    valid: avgColorVariation <= 0.05,
                    error: avgColorVariation > 0.05 ? 'Only chest X-ray images allowed.' : 'Only chest X-ray images allowed.'
                });
                URL.revokeObjectURL(img.src); // Free memory
            };
            img.onerror = function() {
                callback({ valid: false, error: 'Failed to load image.' });
            };
            img.src = URL.createObjectURL(file);
        }

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('xray_image');
            const file = fileInput.files[0];
            const errorDiv = document.getElementById('uploadError');
            const spinner = document.getElementById('spinner');
            const btnText = document.querySelector('.btn-text');

            if (!file) {
                errorDiv.textContent = 'Please select an image to analyze.';
                errorDiv.style.display = 'block';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (!allowedTypes.includes(file.type)) {
                errorDiv.textContent = 'Only JPEG, JPG, or PNG files allowed.';
                errorDiv.style.display = 'block';
                fileInput.value = '';
                return;
            }
            if (file.size > maxSize) {
                errorDiv.textContent = 'File size exceeds 5MB.';
                errorDiv.style.display = 'block';
                fileInput.value = '';
                return;
            }

            spinner.style.display = 'inline-block';
            btnText.textContent = 'Validating...';
            isGrayscale(file, function(result) {
                spinner.style.display = 'none';
                btnText.textContent = 'Analyze X-Ray';
                if (!result.valid) {
                    errorDiv.textContent = result.error;
                    errorDiv.style.display = 'block';
                    fileInput.value = '';
                    return;
                }
                errorDiv.style.display = 'none';
                document.getElementById('uploadForm').submit();
            });
        });

        document.getElementById('xray_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const errorDiv = document.getElementById('uploadError');
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (!allowedTypes.includes(file.type)) {
                    errorDiv.textContent = 'Only JPEG, JPG, or PNG files allowed.';
                    errorDiv.style.display = 'block';
                    this.value = '';
                    return;
                }
                if (file.size > maxSize) {
                    errorDiv.textContent = 'File size exceeds 5MB.';
                    errorDiv.style.display = 'block';
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('fileName').textContent = file.name;
                    document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(2) + ' KB';
                    document.getElementById('imagePreviewContainer').style.display = 'block';
                    errorDiv.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                errorDiv.style.display = 'none';
            }
        });

        document.getElementById('clearPreview').addEventListener('click', function() {
            document.getElementById('imagePreview').src = '';
            document.getElementById('fileName').textContent = '';
            document.getElementById('fileSize').textContent = '';
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('xray_image').value = '';
            document.getElementById('uploadError').style.display = 'none';
        });
    </script>
</body>
</html>