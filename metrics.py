from PIL import Image
import numpy as np

# Function to extract metrics from an image
def extract_metrics(image_path):
    # Open and convert image to grayscale
    img = Image.open(image_path).convert('L')
    pixels = np.array(img)
    
    # Get image dimensions
    height, width = pixels.shape
    
    # Calculate overall metrics
    mean_intensity = np.mean(pixels)
    std_dev = np.std(pixels)
    histogram, _ = np.histogram(pixels, bins=256, range=(0, 256), density=True)
    dark_pixels = np.sum(histogram[:100]) * 100  # Percentage of pixels 0-100
    bright_pixels = np.sum(histogram[150:]) * 100  # Percentage of pixels 150-255
    
    # Define lung and center regions (adjust percentages based on your X-ray layout)
    lung_left = pixels[:int(height * 0.6), :int(width * 0.35)]  # Upper-left lung
    lung_right = pixels[:int(height * 0.6), int(width * 0.65):]  # Upper-right lung
    center = pixels[int(height * 0.2):int(height * 0.8), int(width * 0.35):int(width * 0.65)]  # Center region
    
    # Calculate averages for regions
    lung_left_avg = np.mean(lung_left) if lung_left.size > 0 else 0
    lung_right_avg = np.mean(lung_right) if lung_right.size > 0 else 0
    center_avg = np.mean(center) if center.size > 0 else 0
    
    # Print results
    print(f"Image: {image_path}")
    print(f"Mean Intensity: {mean_intensity:.2f}")
    print(f"Standard Deviation: {std_dev:.2f}")
    print(f"Dark Pixels (0-100): {dark_pixels:.2f}%")
    print(f"Bright Pixels (150-255): {bright_pixels:.2f}%")
    print(f"Lung Left Average: {lung_left_avg:.2f}")
    print(f"Lung Right Average: {lung_right_avg:.2f}")
    print(f"Center Average: {center_avg:.2f}")
    print("---")

# Paths to your X-ray images
image_paths = [
    "/home/nayan/pneumonia/archive/train/NORMAL/IM-0115-0001.jpeg",  # Replace with your first image path
    "/home/nayan/pneumonia/images/2e452c3593afa8ada4ffe123bd15198a.jpg"   # Replace with your second image path
]

# Extract metrics for each image
for path in image_paths:
    extract_metrics(path)