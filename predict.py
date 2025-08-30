import sys
import numpy as np
from tensorflow.keras.models import load_model
from tensorflow.keras.preprocessing import image
 
def predict_image(img_path):
    # Load model
    model = load_model('/home/nayan/pneumonia/ml/models/pneumonia_cnn.h5')
    
    # Load and preprocess image
    img = image.load_img(img_path, target_size=(150, 150))
    img_array = image.img_to_array(img)
    img_array = np.expand_dims(img_array, axis=0)
    img_array /= 255.0
    
    # Make prediction
    prediction = model.predict(img_array)
    return {
        'prediction': 'Pneumonia' if prediction[0][0] > 0.5 else 'Normal',
        'confidence': float(prediction[0][0]) if prediction[0][0] > 0.5 else float(1 - prediction[0][0])
    }

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python predict.py <image_path>")
        sys.exit(1)
    
    result = predict_image(sys.argv[1])
    print(f"Result: {result['prediction']} (Confidence: {result['confidence']:.2%})")