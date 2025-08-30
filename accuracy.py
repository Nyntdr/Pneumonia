from tensorflow.keras.models import load_model
from tensorflow.keras.preprocessing.image import ImageDataGenerator

# === Paths (update as needed) ===
base_path = '/home/nayan/pneumonia/archive'
train_dir = f'{base_path}/train'
val_dir = f'{base_path}/val'
test_dir = f'{base_path}/test'

# === Load trained model ===
model = load_model('/home/nayan/pneumonia/ml/models/pneumonia_cnn.h5')

# === ImageDataGenerator ===
datagen = ImageDataGenerator(rescale=1.0 / 255)

# === Evaluation Function ===
def evaluate(directory, label):
    generator = datagen.flow_from_directory(
        directory,
        target_size=(150, 150),
        batch_size=32,
        class_mode='binary',
        shuffle=False
    )
    loss, accuracy = model.evaluate(generator)
    print(f"{label} Set - Loss: {loss:.4f}, Accuracy: {accuracy * 100:.2f}%")

# === Run evaluations ===
evaluate(train_dir, "Training")
evaluate(val_dir, "Validation")
evaluate(test_dir, "Test")
