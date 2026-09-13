#!/usr/bin/env python3
"""Isolated Asterisk DYNAMIC_FEATURES probe. Never calls a physical device.

Run on the Asterisk host. --prepare only creates fixture configs; --run uses
loopback SIP endpoints and a loopback HTTP callback with synthetic sessions.
No subscriber credentials, push tokens, or production database are involved.
"""

import argparse
import json
import pathlib
import re
import secrets
import socket
import struct
import subprocess
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

VISITOR = "127.77.0.2"
RESIDENT = "127.77.0.3"
SIP_PORT = 55163
HTTP_PORT = 55164
AST_PORT = 50601
FEATURE = "rbt_vdoor_probe"


def ast(command):
    return subprocess.check_output(["asterisk", "-rx", command], text=True)


def prepare(directory):
    directory.mkdir(parents=True, exist_ok=True)
    pjsip = ""
    for name, ip in (("visitor", VISITOR), ("resident", RESIDENT)):
        endpoint = f"rbt-vdoor-probe-{name}"
        pjsip += f"""
[{endpoint}]
type=endpoint
context=rbt-vdoor-probe
disallow=all
allow=alaw
direct_media=no
dtmf_mode=rfc4733
identify_by=ip
aors={endpoint}

[{endpoint}]
type=aor
contact=sip:{name}@{ip}:{SIP_PORT}\\;transport=tcp

[{endpoint}]
type=identify
endpoint={endpoint}
match={ip}/32
"""
    (directory / "pjsip.conf").write_text(pjsip)
    (directory / "features.conf").write_text(
        f'[applicationmap]\n{FEATURE} = 5,self,Gosub,"rbt-vdoor-probe-open,s,1"\n'
    )
    dialplan = """
[rbt-vdoor-probe]
exten => _X!,1,Hangup()
"""
    # All session capabilities originate in this trusted dialplan. SIP callers
    # cannot supply or override these values through headers or called numbers.
    for route in ("direct", "local", "local-n", "ordinary", "expired", "wrong-leg", "closed"):
        if route in ("local", "local-n"):
            dest = "Local/s@rbt-vdoor-probe-local" + ("/n" if route == "local-n" else "")
            options = "g"
        else:
            dest = "PJSIP/rbt-vdoor-probe-resident"
            options = "g" if route == "ordinary" else "gb(rbt-vdoor-probe-resident^s^1)"
        dialplan += f"""
exten => {route},1,Set(__VD_PROBE_SESSION=${{UNIQUEID}})
 same => n,Set(__VD_PROBE_MODE={route})
 same => n,Set(DYNAMIC_FEATURES=)
 same => n,Set(VD_PROBE_ROLE=visitor)
 same => n,Set(CURLOPT(conntimeout)=1)
 same => n,Set(CURLOPT(httptimeout)=2)
 same => n,Set(VD_PROBE_CREATED=${{CURL(http://127.0.0.1:{HTTP_PORT}/create,session=${{VD_PROBE_SESSION}}&mode={route})}})
 same => n,Dial({dest},15,{options})
 same => n,Hangup()
"""
    dialplan += f"""
[rbt-vdoor-probe-local]
exten => s,1,Set(DYNAMIC_FEATURES=)
 same => n,Dial(PJSIP/rbt-vdoor-probe-resident,15,gb(rbt-vdoor-probe-resident^s^1))
 same => n,Hangup()

[rbt-vdoor-probe-resident]
exten => s,1,Set(DYNAMIC_FEATURES={FEATURE})
 same => n,Set(VD_PROBE_ROLE=resident)
 same => n,Set(CURLOPT(conntimeout)=1)
 same => n,Set(CURLOPT(httptimeout)=2)
 same => n,Set(VD_PROBE_BOUND=${{CURL(http://127.0.0.1:{HTTP_PORT}/bind,session=${{VD_PROBE_SESSION}}&channel=${{CHANNEL}}&uniqueid=${{UNIQUEID}})}})
 same => n,Return()

[rbt-vdoor-probe-open]
exten => s,1,Set(CURLOPT(conntimeout)=1)
 same => n,Set(CURLOPT(httptimeout)=2)
 same => n,Set(VD_PROBE_RESULT=${{CURL(http://127.0.0.1:{HTTP_PORT}/open,session=${{VD_PROBE_SESSION}}&role=${{VD_PROBE_ROLE}}&channel=${{CHANNEL}}&uniqueid=${{UNIQUEID}})}})
 same => n,Verbose(1,VD_PROBE_RESULT=${{VD_PROBE_RESULT}})
 same => n,Return()
"""
    (directory / "extensions.conf").write_text(dialplan)


class CallbackState:
    def __init__(self):
        self.sessions = {}
        self.events = []
        self.lock = threading.Lock()

    def request(self, path, data):
        with self.lock:
            sid = data.get("session", "")
            if path == "/create":
                mode = data["mode"]
                self.sessions[sid] = dict(mode=mode, expires=time.monotonic() + (0 if mode == "expired" else 60),
                                          active=mode != "closed", opened=False)
                return "created"
            session = self.sessions.get(sid)
            if not session:
                result = "unknown-session"
            elif path == "/bind":
                session["channel"] = data["channel"]
                session["uniqueid"] = "different-leg" if session["mode"] == "wrong-leg" else data["uniqueid"]
                return "bound"
            elif path != "/open":
                result = "invalid-action"
            elif data.get("role") != "resident":
                result = "wrong-role"
            elif (data.get("channel"), data.get("uniqueid")) != (session.get("channel"), session.get("uniqueid")):
                result = "wrong-leg"
            elif not session["active"]:
                result = "closed"
            elif time.monotonic() >= session["expires"]:
                result = "expired"
            elif session["opened"]:
                result = "duplicate"
            else:
                session["opened"] = True
                result = "accepted-dry-run"
            self.events.append(dict(session=sid, result=result, **{k: v for k, v in data.items() if k != "session"}))
            return result


def callback_server(state):
    from urllib.parse import parse_qsl

    class Handler(BaseHTTPRequestHandler):
        def do_POST(self):
            length = int(self.headers.get("Content-Length", "0"))
            if length > 4096:
                self.send_error(413)
                return
            data = dict(parse_qsl(self.rfile.read(length).decode()))
            result = state.request(self.path, data).encode()
            self.send_response(200)
            self.send_header("Content-Type", "text/plain")
            self.send_header("Content-Length", str(len(result)))
            self.end_headers()
            self.wfile.write(result)

        def log_message(self, *_):
            pass

    server = ThreadingHTTPServer(("127.0.0.1", HTTP_PORT), Handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    return server


class SIP:
    def __init__(self, conn):
        self.conn = conn
        self.buffer = b""

    def send(self, start, headers, body=""):
        headers = list(headers) + [("Content-Length", str(len(body.encode()))) ]
        message = start + "\r\n" + "\r\n".join(f"{k}: {v}" for k, v in headers) + "\r\n\r\n" + body
        self.conn.sendall(message.encode())

    def receive(self, timeout=5):
        self.conn.settimeout(timeout)
        while True:
            split = self.buffer.find(b"\r\n\r\n")
            if split >= 0:
                raw = self.buffer[:split].decode()
                lines = raw.split("\r\n")
                headers = [line.split(":", 1) for line in lines[1:] if ":" in line]
                headers = [(k.lower(), v.strip()) for k, v in headers]
                size = int(dict(headers).get("content-length", "0"))
                end = split + 4 + size
                if len(self.buffer) >= end:
                    body = self.buffer[split + 4:end].decode()
                    self.buffer = self.buffer[end:]
                    return lines[0], headers, body
            data = self.conn.recv(65536)
            if not data:
                raise EOFError("SIP connection closed")
            self.buffer += data

    def reply(self, message, status=200, body="", contact=None, tag=None):
        _, headers, _ = message
        selected = [(k, v) for k, v in headers if k in ("via", "from", "to", "call-id", "cseq")]
        if tag:
            selected = [(k, v + f";tag={tag}" if k == "to" and ";tag=" not in v else v) for k, v in selected]
        if contact:
            selected.append(("Contact", contact))
        if body:
            selected.append(("Content-Type", "application/sdp"))
        self.send(f"SIP/2.0 {status} OK", selected, body)


class Media:
    def __init__(self, ip):
        self.ip = ip
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.socket.bind((ip, 0))
        self.socket.settimeout(.02)
        self.port = self.socket.getsockname()[1]
        self.running = True
        self.destination = None
        self.received = 0
        self.sent = 0
        self.sequence = 0
        self.timestamp = 0
        threading.Thread(target=self.loop, daemon=True).start()

    def sdp(self):
        return (f"v=0\r\no=- 1 1 IN IP4 {self.ip}\r\ns=Virtual door probe\r\nc=IN IP4 {self.ip}\r\n"
                f"t=0 0\r\nm=audio {self.port} RTP/AVP 8 101\r\na=rtpmap:8 PCMA/8000\r\n"
                "a=rtpmap:101 telephone-event/8000\r\na=fmtp:101 0-16\r\na=sendrecv\r\na=ptime:20\r\n")

    def remote(self, sdp):
        ip = re.search(r"c=IN IP4 ([^\s]+)", sdp)
        port = re.search(r"m=audio (\d+)", sdp)
        if not ip or not port:
            raise AssertionError("Missing remote audio SDP")
        self.destination = (ip[1], int(port[1]))

    def loop(self):
        while self.running:
            try:
                if self.destination:
                    packet = struct.pack("!BBHII", 0x80, 8, self.sequence % 65536, self.timestamp, 1234567)
                    self.socket.sendto(packet + b"\xd5" * 160, self.destination)
                    self.sent += 1
                    self.sequence += 1
                    self.timestamp += 160
                data, _ = self.socket.recvfrom(8192)
                if len(data) >= 12 and (data[1] & 127) == 8:
                    self.received += 1
                time.sleep(.01)
            except socket.timeout:
                pass
            except OSError:
                break

    def close(self):
        self.running = False
        self.socket.close()


class Dialog:
    def __init__(self, sip, ip, local, remote, call_id, target, cseq=1):
        self.sip, self.ip, self.local, self.remote = sip, ip, local, remote
        self.call_id, self.target, self.cseq = call_id, target, cseq

    def send(self, method, body="", content_type=None, cseq=None):
        if cseq is None:
            self.cseq += 1
            cseq = self.cseq
        headers = [("Via", f"SIP/2.0/TCP {self.ip}:{SIP_PORT};branch=z9hG4bK{secrets.token_hex(8)};rport"),
                   ("Max-Forwards", "70"), ("From", self.local), ("To", self.remote),
                   ("Call-ID", self.call_id), ("CSeq", f"{cseq} {method}"),
                   ("Contact", f"<sip:probe@{self.ip}:{SIP_PORT};transport=tcp>")]
        if content_type:
            headers.append(("Content-Type", content_type))
        self.sip.send(f"{method} {self.target} SIP/2.0", headers, body)
        if method == "ACK":
            return
        deadline = time.monotonic() + 5
        while time.monotonic() < deadline:
            msg = self.sip.receive()
            if msg[0].startswith("SIP/2.0") and dict(msg[1]).get("cseq") == f"{cseq} {method}":
                if not msg[0].startswith("SIP/2.0 200"):
                    raise AssertionError(f"{method} failed: {msg[0]}")
                return
            if not msg[0].startswith("SIP/2.0"):
                self.sip.reply(msg)
        raise TimeoutError(method)

    def digit(self, value):
        self.send("INFO", f"Signal={value}\r\nDuration=160\r\n", "application/dtmf-relay")


def connect_call(route):
    listener = socket.socket()
    listener.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    listener.bind((RESIDENT, SIP_PORT))
    listener.listen(1)
    listener.settimeout(10)
    visitor_media, resident_media = Media(VISITOR), Media(RESIDENT)
    visitor_conn = socket.socket()
    visitor_conn.bind((VISITOR, 0))
    visitor_conn.connect(("127.0.0.1", AST_PORT))
    visitor = SIP(visitor_conn)
    call_id, tag = secrets.token_hex(12), secrets.token_hex(8)
    target = f"sip:{route}@127.0.0.1:{AST_PORT}"
    local = f"<sip:visitor@{VISITOR}>;tag={tag}"
    remote = f"<{target}>"
    visitor.send(f"INVITE {target} SIP/2.0", [
        ("Via", f"SIP/2.0/TCP {VISITOR}:{visitor_conn.getsockname()[1]};branch=z9hG4bK{secrets.token_hex(8)};rport"),
        ("Max-Forwards", "70"), ("From", local), ("To", remote), ("Call-ID", call_id),
        ("CSeq", "1 INVITE"), ("Contact", f"<sip:visitor@{VISITOR}:{visitor_conn.getsockname()[1]};transport=tcp>"),
        ("Content-Type", "application/sdp")], visitor_media.sdp())
    resident_conn, _ = listener.accept()
    listener.close()
    resident = SIP(resident_conn)
    msg = resident.receive()
    while not msg[0].startswith("INVITE "):
        resident.reply(msg)
        msg = resident.receive()
    rh = dict(msg[1])
    resident_media.remote(msg[2])
    resident_tag = secrets.token_hex(8)
    resident.reply(msg, body=resident_media.sdp(), contact=f"<sip:resident@{RESIDENT}:{SIP_PORT};transport=tcp>", tag=resident_tag)
    while True:
        response = visitor.receive()
        if response[0].startswith("SIP/2.0 200"):
            break
        if response[0].startswith(("SIP/2.0 4", "SIP/2.0 5", "SIP/2.0 6")):
            raise AssertionError(response[0])
    vh = dict(response[1])
    visitor_media.remote(response[2])
    vdialog = Dialog(visitor, VISITOR, local, vh["to"], call_id, vh["contact"].strip("<>"))
    vdialog.send("ACK", cseq=1)
    while True:
        message = resident.receive()
        if message[0].startswith("ACK "):
            break
        resident.reply(message)
    rdialog = Dialog(resident, RESIDENT, rh["to"] + f";tag={resident_tag}", rh["from"],
                      rh["call-id"], rh["contact"].strip("<>"), cseq=100)
    return vdialog, rdialog, visitor_media, resident_media


def wait_for(predicate, description, timeout=4):
    deadline = time.monotonic() + timeout
    while time.monotonic() < deadline:
        if predicate():
            return
        time.sleep(.05)
    raise AssertionError(description)


def run(output):
    state = CallbackState()
    server = callback_server(state)
    results = []
    try:
        for route in ("direct", "local", "local-n", "ordinary", "expired", "wrong-leg", "closed"):
            start_events = len(state.events)
            visitor, resident, vm, rm = connect_call(route)
            try:
                wait_for(lambda: vm.received > 5 and rm.received > 5, "RTP not flowing in both directions")
                # An attacker on the visitor side must not trigger the callback.
                visitor.digit("5")
                time.sleep(.5)
                assert len(state.events) == start_events, "Visitor activated resident feature"
                resident.digit("4")
                time.sleep(.3)
                assert len(state.events) == start_events, "Wrong digit activated feature"
                before = (vm.received, rm.received)
                resident.digit("5")
                expected = {"ordinary": None, "expired": "expired", "wrong-leg": "wrong-leg", "closed": "closed"}.get(route, "accepted-dry-run")
                if expected is None:
                    time.sleep(.5)
                    assert len(state.events) == start_events, "Ordinary call activated feature"
                else:
                    wait_for(lambda: len(state.events) == start_events + 1, "Resident feature did not call backend")
                    assert state.events[-1]["result"] == expected, state.events[-1]
                    if expected == "accepted-dry-run":
                        resident.digit("5")
                        wait_for(lambda: len(state.events) == start_events + 2, "Repeat did not reach idempotency guard")
                        assert state.events[-1]["result"] == "duplicate", state.events[-1]
                wait_for(lambda: vm.received > before[0] + 5 and rm.received > before[1] + 5, "RTP stopped after feature")
                channels = ast("core show channels concise")
                bridges = ast("bridge show all")
                row = dict(route=route, passed=True, expected=expected, events=state.events[start_events:],
                           rtpReceived=dict(visitor=vm.received, resident=rm.received), channels=channels, bridges=bridges)
                results.append(row)
                print(json.dumps({k: v for k, v in row.items() if k not in ("channels", "bridges", "events")}), flush=True)
            finally:
                try:
                    visitor.send("BYE")
                except (OSError, EOFError, TimeoutError, AssertionError):
                    pass
                for item in (visitor, resident):
                    item.sip.conn.close()
                vm.close()
                rm.close()
                time.sleep(.3)
    finally:
        server.shutdown()
        output.write_text(json.dumps(dict(results=results, events=state.events), indent=2) + "\n")
    print(json.dumps(dict(passed=len(results), total=7, physicalDoorCommands=0)), flush=True)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--prepare", type=pathlib.Path)
    group.add_argument("--run", type=pathlib.Path, metavar="RESULT_JSON")
    args = parser.parse_args()
    if args.prepare:
        prepare(args.prepare)
    else:
        run(args.run)
