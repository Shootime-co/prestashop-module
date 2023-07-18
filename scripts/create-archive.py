import sys
import zipfile
from pathlib import Path

# Define the directory and exclusion list
CURRENT_DIRECTORY = Path.cwd()
EXCLUSION_LIST = {
    ".DS_Store",
    ".git",
    ".github",
    ".gitignore",
    ".gitignore",
    ".php-cs-fixer.dist.php",
    ".pre-commit-config.yaml",
    ".idea",
    ".tool-versions",
    "Dockerfile",
    "scripts",
    "venv",
}


def main(archive_name: str):
    print(f"Creating file {archive_name}...")
    exclusion_list = EXCLUSION_LIST | {archive_name}
    with zipfile.ZipFile(archive_name, "w") as zf:
        for file_path in CURRENT_DIRECTORY.glob("**/*"):
            relative_path = file_path.relative_to(CURRENT_DIRECTORY)
            parent_dir = relative_path.parts[0]
            if parent_dir in exclusion_list:
                continue

            archive_path = Path("shootime") / relative_path
            print(f"Adding {archive_path}...")
            zf.write(file_path, arcname=archive_path)

    print(f"Zip file created: {archive_name}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1]))
