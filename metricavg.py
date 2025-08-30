import os
import numpy as np
from PIL import Image
import pandas as pd

def extract_metrics(image_path):
    try:
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
        
        # Define lung and center regions
        lung_left = pixels[:int(height * 0.6), :int(width * 0.35)]  # Upper-left lung
        lung_right = pixels[:int(height * 0.6), int(width * 0.65):]  # Upper-right lung
        center = pixels[int(height * 0.2):int(height * 0.8), int(width * 0.35):int(width * 0.65)]  # Center region
        
        # Calculate averages for regions
        lung_left_avg = np.mean(lung_left) if lung_left.size > 0 else 0
        lung_right_avg = np.mean(lung_right) if lung_right.size > 0 else 0
        center_avg = np.mean(center) if center.size > 0 else 0
        
        return {
            'image_path': image_path,
            'mean_intensity': mean_intensity,
            'std_dev': std_dev,
            'dark_pixels': dark_pixels,
            'bright_pixels': bright_pixels,
            'lung_left_avg': lung_left_avg,
            'lung_right_avg': lung_right_avg,
            'center_avg': center_avg
        }
    except Exception as e:
        print(f"Error processing {image_path}: {str(e)}")
        return None

def analyze_dataset(directories):
    metrics_list = []
    
    # Supported image extensions
    valid_extensions = ('.jpg', '.jpeg', '.png')
    
    # Iterate through directories
    for directory in directories:
        for root, _, files in os.walk(directory):
            for file in files:
                if file.lower().endswith(valid_extensions):
                    image_path = os.path.join(root, file)
                    metrics = extract_metrics(image_path)
                    if metrics:
                        metrics_list.append(metrics)
    
    # Convert to DataFrame for analysis
    df = pd.DataFrame(metrics_list)
    
    # Compute ranges and averages
    result = {
        'mean_intensity': {
            'min': df['mean_intensity'].min(),
            'max': df['mean_intensity'].max(),
            'avg': df['mean_intensity'].mean()
        },
        'std_dev': {
            'min': df['std_dev'].min(),
            'max': df['std_dev'].max(),
            'avg': df['std_dev'].mean()
        },
        'dark_pixels': {
            'min': df['dark_pixels'].min(),
            'max': df['dark_pixels'].max(),
            'avg': df['dark_pixels'].mean()
        },
        'bright_pixels': {
            'min': df['bright_pixels'].min(),
            'max': df['bright_pixels'].max(),
            'avg': df['bright_pixels'].mean()
        },
        'lung_left_avg': {
            'min': df['lung_left_avg'].min(),
            'max': df['lung_left_avg'].max(),
            'avg': df['lung_left_avg'].mean()
        },
        'lung_right_avg': {
            'min': df['lung_right_avg'].min(),
            'max': df['lung_right_avg'].max(),
            'avg': df['lung_right_avg'].mean()
        },
        'center_avg': {
            'min': df['center_avg'].min(),
            'max': df['center_avg'].max(),
            'avg': df['center_avg'].mean()
        }
    }
    
    return result, len(metrics_list)

def print_results(metrics_summary, num_images):
    print(f"Analyzed {num_images} images.")
    print("\nMetric Ranges and Averages:")
    print("-" * 50)
    print(f"{'Metric':<20} {'Min':>10} {'Max':>10} {'Average':>10}")
    print("-" * 50)
    for metric, values in metrics_summary.items():
        print(f"{metric.replace('_', ' ').title():<20} {values['min']:>10.2f} {values['max']:>10.2f} {values['avg']:>10.2f}")

# Dataset directories
train_dir = '/home/nayan/pneumonia/archive/train'
val_dir = '/home/nayan/pneumonia/archive/val'
directories = [train_dir, val_dir]

# Analyze dataset and print results
metrics_summary, num_images = analyze_dataset(directories)
print_results(metrics_summary, num_images)