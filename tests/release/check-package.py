#!/usr/bin/env python3
"""Verify the installable ZIP and bundled Arabic catalog using the standard library."""
import ast
import gettext
import io
from pathlib import Path
import re
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SLUG = sys.argv[2] if len(sys.argv) > 2 else "wp-ersaal"


def catalog(text):
    entries = []
    entry = {}
    field = None
    for line in text.splitlines() + [""]:
        if not line.strip():
            if "msgid" in entry:
                entries.append(entry)
            entry, field = {}, None
        elif line.startswith("#, "):
            entry["fuzzy"] = "fuzzy" in line[3:].split(", ")
        elif line.startswith("#"):
            continue
        elif line.startswith('"'):
            entry[field] += ast.literal_eval(line)
        else:
            field, value = line.split(" ", 1)
            entry[field] = ast.literal_eval(value)
    return [entry for entry in entries if entry["msgid"]]


def key(entry):
    return (entry.get("msgctxt", ""), entry["msgid"], entry.get("msgid_plural", ""))


with zipfile.ZipFile(sys.argv[1]) as archive:
    assert archive.testzip() is None, "Corrupt ZIP"
    names = archive.namelist()
    assert {name.split("/")[0] for name in names} == {SLUG}, "Unexpected ZIP root"
    files = {name.removeprefix(SLUG + "/") for name in names if not name.endswith("/")}
    for name in files:
        parts = Path(name).parts
        assert not any(part.startswith(".") for part in parts), name
        assert parts[0] not in {"assets", "tests", "docs", "bin", "dist", "dist-release"}, name
        assert not name.endswith((".md", "~")), name
        assert name not in {"composer.json", "composer.lock", "run_dbdelta.php"}, name
        assert not parts[-1].startswith(("phpunit.xml", "go.work")), name
    for directory in ("admin/assets", "src", "vendor"):
        for source in (ROOT / directory).rglob("*"):
            if source.is_file():
                name = source.relative_to(ROOT).as_posix()
                assert name in files, f"Missing runtime file: {name}"
                assert archive.read(SLUG + "/" + name) == source.read_bytes(), name

    def read(name):
        return archive.read(SLUG + "/" + name)

    main = read("ersaal.php").decode()
    version = re.search(r"Version:\s*(\S+)", main)[1]
    assert f"define('ERSAAL_VERSION', '{version}');" in main
    assert f"Stable tag: {version}" in read("readme.txt").decode()
    po = catalog(read("languages/ersaal-sms-gateway-ar.po").decode())
    pot = catalog(read("languages/ersaal-sms-gateway.pot").decode())
    assert {key(entry) for entry in po} == {key(entry) for entry in pot}, "PO/POT mismatch"
    mo = gettext.GNUTranslations(io.BytesIO(read("languages/ersaal-sms-gateway-ar.mo")))
    assert mo.info()["language"] == "ar"
    assert mo.info()["project-id-version"] == f"Ersaal SMS Gateway {version}"
    for entry in po:
        assert not entry.get("fuzzy"), entry["msgid"]
        prefix = entry.get("msgctxt", "")
        msgid = (prefix + "\x04" if prefix else "") + entry["msgid"]
        if "msgid_plural" in entry:
            for index in range(6):
                value = entry[f"msgstr[{index}]"]
                assert value and mo._catalog[(msgid, index)] == value, msgid
        else:
            assert entry["msgstr"] and mo._catalog[msgid] == entry["msgstr"], msgid
    assert mo.gettext("Settings") == "الإعدادات"
    assert mo.gettext("User ID") == "معرّف المستخدم"
    print(f"PASS: v{version} ZIP, runtime files, and {len(po)} Arabic translations")
