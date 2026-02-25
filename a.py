import json
import time
import pickle
import requests
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build

CREDENTIALS_FILE = "/home/claw/.openclaw/credentials/oauth-client.json"
TOKEN_FILE = "/home/claw/.openclaw/credentials/oauth-token.pickle"
SCOPES = ["https://www.googleapis.com/auth/drive.file"]
FOLDER_ID = "1e0PcnPMTjVA5sFWs8HGqHQBUm_vaWZ1F"

# Load OAuth client config
with open(CREDENTIALS_FILE) as f:
    config = json.load(f)

client_id = config["installed"]["client_id"]
client_secret = config["installed"]["client_secret"]

# Request device code
response = requests.post(
    "https://oauth2.googleapis.com/device/code",
    data={"client_id": client_id, "scope": " ".join(SCOPES)},
    timeout=30,
)
print("DEVICE_CODE_HTTP:", response.status_code)
print("DEVICE_CODE_BODY:", response.text)

device_data = response.json()
verification_url = device_data.get("verification_url") or device_data.get("verification_uri")

if not verification_url:
    raise SystemExit(device_data)

print("\nBuka:", verification_url)
print("Code:", device_data["user_code"])
print("Approve dulu...\n")

interval = int(device_data.get("interval", 5))

# Poll for access token
while True:
    token = requests.post(
        "https://oauth2.googleapis.com/token",
        data={
            "client_id": client_id,
            "client_secret": client_secret,
            "device_code": device_data["device_code"],
            "grant_type": "urn:ietf:params:oauth:grant-type:device_code",
        },
        timeout=30,
    ).json()

    if "access_token" in token:
        break

    if token.get("error") in ("authorization_pending", "slow_down"):
        time.sleep(interval)
        continue

    raise SystemExit(token)

# Build and save credentials
creds = Credentials(
    token=token["access_token"],
    refresh_token=token.get("refresh_token"),
    token_uri="https://oauth2.googleapis.com/token",
    client_id=client_id,
    client_secret=client_secret,
    scopes=SCOPES,
)

with open(TOKEN_FILE, "wb") as f:
    pickle.dump(creds, f)

# Create a Google Doc in the specified folder
drive_service = build("drive", "v3", credentials=creds)
created_file = drive_service.files().create(
    body={
        "name": "say-hi-oauth",
        "mimeType": "application/vnd.google-apps.document",
        "parents": [FOLDER_ID],
    },
    fields="id,name,webViewLink",
).execute()

print("CREATED:", created_file)