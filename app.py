from flask import Flask, request, render_template, jsonify
from tensorflow.keras.models import load_model
from tensorflow.keras.preprocessing import image
import numpy as np
import os

app = Flask(__name__, template_folder="../user/templates", static_folder="../style")

# Load the trained model
model = load_model("/home/nayan/pneumonia/ml/models/pneumonia_cnn.h5")

# Path for uploaded images
UPLOAD_FOLDER = "../uploads"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
app.config["UPLOAD_FOLDER"] = UPLOAD_FOLDER

# Predict function from predict.py
def predict_image(img_path):
    img = image.load_img(img_path, target_size=(150, 150))
    img_array = image.img_to_array(img)
    img_array = np.expand_dims(img_array, axis=0)
    img_array /= 255.0
    prediction = model.predict(img_array)
    return {
        "prediction": "Pneumonia" if prediction[0][0] > 0.5 else "Normal",
        "confidence": float(prediction[0][0]) if prediction[0][0] > 0.5 else float(1 - prediction[0][0])
    }

# Web interface for manual testing
@app.route("/", methods=["GET", "POST"])
def upload_file():
    if request.method == "POST":
        if "file" not in request.files:
            return render_template("index.html", message="No file uploaded")
        file = request.files["file"]
        if file.filename == "":
            return render_template("index.html", message="No file selected")
        if file:
            file_path = os.path.join(app.config["UPLOAD_FOLDER"], file.filename)
            file.save(file_path)
            result = predict_image(file_path)
            message = f"Prediction: {result['prediction']} (Confidence: {result['confidence']:.2%})"
            return render_template("index.html", message=message, image_file=file.filename)
    return render_template("index.html", message=None)

# API endpoint for PHP frontend
@app.route("/api/predict", methods=["POST"])
def api_predict():
    if "file" not in request.files:
        return jsonify({"error": "No file uploaded"}), 400
    file = request.files["file"]
    if file.filename == "":
        return jsonify({"error": "No file selected"}), 400
    if not file.mimetype in ["image/jpeg", "image/png"]:
        return jsonify({"error": "Only JPEG or PNG files allowed"}), 400
    file_path = os.path.join(app.config["UPLOAD_FOLDER"], file.filename)
    file.save(file_path)
    result = predict_image(file_path)
    return jsonify({
        "prediction": result["prediction"],
        "confidence": f"{result['confidence']:.2%}",
        "image_file": file.filename
    })

if __name__ == "__main__":
    app.run(debug=True, host="0.0.0.0", port=5000)