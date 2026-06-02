import os

files = os.listdir(".")
print("All files in workspace:")
for f in files:
    print(f"  {f} | is_dir: {os.path.isdir(f)}")
