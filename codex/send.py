"""
Panduan mendapatkan CHAT_ID:

1. Isi BOT_TOKEN dengan token dari BotFather.
2. Buka Telegram, cari bot kamu, lalu kirim pesan "/start".
3. Jalankan perintah ini dari terminal:

   python send.py --chat-id

4. Salin angka chat ID yang muncul ke variabel CHAT_ID di bawah.
5. Setelah CHAT_ID terisi, kirim pesan dengan:

   python send.py "contoh pesan"

   Secara default perintah menunggu respons Telegram agar kegagalan tidak
   tersembunyi. Jika ingin mode background, gunakan:

   python send.py --background "contoh pesan"

Untuk grup:
1. Tambahkan bot ke grup.
2. Kirim pesan apa saja di grup.
3. Jalankan lagi:

   python send.py --chat-id

Chat ID grup biasanya diawali angka negatif, misalnya -1001234567890.
"""

import os
import sys

BOT_TOKEN = os.getenv("TELEGRAM_BOT_TOKEN", "8732412628:AAH65tJt5BtNzGLaFSC1IbPH88g76AKrKHU")
CHAT_ID = os.getenv("TELEGRAM_CHAT_ID", "6195650369")
PARSE_MODE = ""
CHAT_ID_COMMANDS = {"--chat-id", "--get-chat-id", "chat-id"}
BACKGROUND_COMMANDS = {"--background", "--async", "--queue"}
SYNC_COMMANDS = {"--wait", "--sync", "--deliver-now"}
BACKGROUND_ENV = "CODEX_SEND_BACKGROUND"
REQUEST_TIMEOUT = 8
CONNECT_TIMEOUT = 2


def build_message(argv: list[str]) -> str:
    if argv:
        return " ".join(argv).strip()

    if not sys.stdin.isatty():
        return sys.stdin.read().strip()

    return ""


def pop_delivery_flags(argv: list[str]) -> tuple[bool, list[str]]:
    send_in_background = any(arg in BACKGROUND_COMMANDS for arg in argv)
    stripped_commands = BACKGROUND_COMMANDS | SYNC_COMMANDS
    return send_in_background, [arg for arg in argv if arg not in stripped_commands]


def queue_background_send(message: str) -> int:
    command = build_curl_command(message)

    if command:
        spawn_detached(command)
    else:
        spawn_python_background(message)

    print("Pesan dijadwalkan ke Telegram tanpa menunggu respons.")
    return 0


def build_curl_command(message: str) -> list[str] | None:
    curl = find_executable("curl")
    if not curl:
        return None

    command = [
        curl,
        "-s",
        "-o",
        os.devnull,
        "--max-time",
        str(REQUEST_TIMEOUT),
        "--connect-timeout",
        str(CONNECT_TIMEOUT),
        "--retry",
        "0",
        "-X",
        "POST",
        f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage",
        "--data-urlencode",
        f"chat_id={CHAT_ID}",
        "--data-urlencode",
        f"text={message}",
        "--data-urlencode",
        "disable_web_page_preview=true",
    ]

    if PARSE_MODE:
        command.extend(["--data-urlencode", f"parse_mode={PARSE_MODE}"])

    return command


def find_executable(name: str) -> str | None:
    names = [name]
    if os.name == "nt" and not name.lower().endswith(".exe"):
        names.insert(0, f"{name}.exe")

    for directory in os.environ.get("PATH", "").split(os.pathsep):
        if not directory:
            continue

        for executable_name in names:
            path = os.path.join(directory, executable_name)
            if os.path.isfile(path):
                return path

    return None


def spawn_python_background(message: str) -> None:
    env = os.environ.copy()
    env[BACKGROUND_ENV] = "1"

    spawn_detached([sys.executable, os.path.abspath(__file__), "--deliver-now", message], env)


def spawn_detached(command: list[str], env: dict[str, str] | None = None) -> None:
    import subprocess

    kwargs = {
        "stdin": subprocess.DEVNULL,
        "stdout": subprocess.DEVNULL,
        "stderr": subprocess.DEVNULL,
        "close_fds": True,
    }

    if env is not None:
        kwargs["env"] = env

    if os.name == "nt":
        kwargs["creationflags"] = subprocess.CREATE_NEW_PROCESS_GROUP | subprocess.DETACHED_PROCESS
    else:
        kwargs["start_new_session"] = True

    subprocess.Popen(command, **kwargs)


def send_telegram_message(token: str, chat_id: str, text: str) -> dict:
    import json
    import urllib.parse
    import urllib.request

    url = f"https://api.telegram.org/bot{token}/sendMessage"
    payload = {
        "chat_id": chat_id,
        "text": text,
        "disable_web_page_preview": True,
    }

    if PARSE_MODE:
        payload["parse_mode"] = PARSE_MODE

    data = urllib.parse.urlencode(payload).encode("utf-8")
    request = urllib.request.Request(url, data=data, method="POST")

    with urllib.request.urlopen(request, timeout=REQUEST_TIMEOUT) as response:
        return json.loads(response.read().decode("utf-8"))


def get_updates(token: str) -> dict:
    import json
    import urllib.request

    url = f"https://api.telegram.org/bot{token}/getUpdates"

    with urllib.request.urlopen(url, timeout=REQUEST_TIMEOUT) as response:
        return json.loads(response.read().decode("utf-8"))


def find_chats(updates: dict) -> list[dict]:
    chats = {}
    update_sources = (
        "message",
        "edited_message",
        "channel_post",
        "edited_channel_post",
        "my_chat_member",
        "chat_member",
        "chat_join_request",
    )

    for update in updates.get("result", []):
        for source in update_sources:
            item = update.get(source)
            if not isinstance(item, dict):
                continue

            chat = item.get("chat")
            if isinstance(chat, dict) and "id" in chat:
                chats[str(chat["id"])] = chat

    return list(chats.values())


def print_chat_ids() -> int:
    import urllib.error

    if BOT_TOKEN.startswith("ISI_"):
        print("Error: isi BOT_TOKEN di bagian atas send.py dulu.", file=sys.stderr)
        return 1

    try:
        updates = get_updates(BOT_TOKEN)
    except urllib.error.HTTPError as error:
        detail = error.read().decode("utf-8", errors="replace")
        print(f"Telegram API error {error.code}: {detail}", file=sys.stderr)
        return 1
    except urllib.error.URLError as error:
        print(f"Gagal terhubung ke Telegram: {error.reason}", file=sys.stderr)
        return 1
    except TimeoutError:
        print("Timeout saat mengambil chat ID dari Telegram.", file=sys.stderr)
        return 1

    if not updates.get("ok"):
        print(f"Telegram API response: {updates}", file=sys.stderr)
        return 1

    chats = find_chats(updates)
    if not chats:
        print("Belum ada chat ID.")
        print("Kirim /start atau pesan apa saja ke bot, lalu jalankan lagi:")
        print("python send.py --chat-id")
        return 1

    print("Chat ID ditemukan:")
    for chat in chats:
        chat_id = chat.get("id")
        chat_type = chat.get("type", "-")
        title = chat.get("title") or chat.get("username") or chat.get("first_name") or "-"
        print(f"- {chat_id} | {chat_type} | {title}")

    return 0


def main() -> int:
    send_in_background, args = pop_delivery_flags(sys.argv[1:])

    if args and args[0] in CHAT_ID_COMMANDS:
        return print_chat_ids()

    message = build_message(args)

    if BOT_TOKEN.startswith("ISI_"):
        print("Error: isi BOT_TOKEN di bagian atas send.py dulu.", file=sys.stderr)
        return 1

    if CHAT_ID.startswith("ISI_"):
        print("Error: isi CHAT_ID di bagian atas send.py dulu.", file=sys.stderr)
        print("Untuk melihat chat ID, jalankan: python send.py --chat-id")
        return 1

    if not message:
        print('Usage: python send.py "contoh pesan"', file=sys.stderr)
        return 1

    if send_in_background:
        return queue_background_send(message)

    try:
        import urllib.error

        result = send_telegram_message(BOT_TOKEN, CHAT_ID, message)
    except urllib.error.HTTPError as error:
        detail = error.read().decode("utf-8", errors="replace")
        print(f"Telegram API error {error.code}: {detail}", file=sys.stderr)
        return 1
    except urllib.error.URLError as error:
        print(f"Gagal terhubung ke Telegram: {error.reason}", file=sys.stderr)
        return 1
    except TimeoutError:
        print("Timeout saat mengirim pesan ke Telegram.", file=sys.stderr)
        return 1

    if not result.get("ok"):
        print(f"Telegram API response: {result}", file=sys.stderr)
        return 1

    print("Pesan terkirim ke Telegram.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
