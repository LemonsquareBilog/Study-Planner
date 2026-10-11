"""Replace Capacitor assets while preserving the native APK and ZIP storage modes."""
from pathlib import Path
import re
import sys
import zipfile

base, assets, output = map(Path, sys.argv[1:])
with zipfile.ZipFile(base) as source, zipfile.ZipFile(output, "w") as destination:
    for entry in source.infolist():
        if (
            entry.filename.startswith("assets/public/")
            or entry.filename in ("assets/capacitor.config.json", "assets/capacitor.plugins.json")
            or re.match(r"^META-INF/.*\.(SF|RSA|DSA|EC|MF)$", entry.filename)
        ):
            continue
        if entry.filename == "resources.arsc" or entry.filename.startswith("lib/"):
            entry.compress_type = zipfile.ZIP_STORED
        destination.writestr(entry, source.read(entry.filename))
    for asset in sorted(assets.rglob("*")):
        if asset.is_file():
            destination.write(asset, "assets/" + asset.relative_to(assets).as_posix(), zipfile.ZIP_DEFLATED)
