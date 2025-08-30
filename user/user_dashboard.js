document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('uploadForm');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('xray_image');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const clearPreview = document.getElementById('clearPreview');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadResult = document.getElementById('uploadResult');

    // Drag and drop handling
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('highlight');
    }

    function unhighlight() {
        dropZone.classList.remove('highlight');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length) {
            fileInput.files = files;
            updatePreview();
        }
    }

    // File input change handler
    fileInput.addEventListener('change', updatePreview);

    // Update preview
    function updatePreview() {
        const file = fileInput.files[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            previewContainer.style.display = 'block';
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
        };
        reader.readAsDataURL(file);
    }

    // Clear preview
    clearPreview.addEventListener('click', function() {
        fileInput.value = '';
        previewContainer.style.display = 'none';
        imagePreview.src = '';
    });

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!fileInput.files.length) {
            showResult('Please select an image file', 'error');
            return;
        }

        const formData = new FormData(form);
        uploadBtn.disabled = true;
        uploadBtn.querySelector('.btn-text').textContent = 'Processing...';
        uploadBtn.querySelector('.spinner').style.display = 'inline-block';

        fetch('process_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult(`
                    <h3>Analysis Result: ${data.result.prediction}</h3>
                    <p>Confidence: ${(data.result.confidence * 100).toFixed(2)}%</p>
                    <img src="${data.imageUrl}" alt="Processed X-Ray" class="result-image">
                    <p>Scan has been saved to your history</p>
                `, 'success');
                
                // Refresh history
                setTimeout(() => location.reload(), 2000);
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }
        })
        .catch(error => {
            showResult(error.message, 'error');
        })
        .finally(() => {
            uploadBtn.disabled = false;
            uploadBtn.querySelector('.btn-text').textContent = 'Analyze X-Ray';
            uploadBtn.querySelector('.spinner').style.display = 'none';
        });
    });

    // Show result message
    function showResult(message, type) {
        uploadResult.innerHTML = `<div class="result-${type}">${message}</div>`;
        uploadResult.style.display = 'block';
    }
});