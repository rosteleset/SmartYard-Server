(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const panel = location.pathname.match(/^\/v\/([a-zA-Z0-9_-]{12})$/)?.[1] || '';
    let metadata, selected, sourceMedia, media, ua, sipSession, call, busy = false, ending = false, doorOpened = false;
    let statusTimer, frameTimer, durationTimer, setupTimer, iceTimer, startedAt, generation = 0;
    let preview;
    let codeMode = false, opening = false;
    const status = (text, kind = '') => { $('status').textContent = text; $('status').className = 'status ' + kind; };
    const headers = () => ({ Authorization: 'Bearer ' + call.token });
    const flatTitle = flat => flat.name?.trim() || 'Квартира ' + flat.number;
    const idleControlTitle = $('control-title').textContent;
    async function api(action, options = {}, query = '') {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);
        try {
            const response = await fetch('/virtual-intercom/api/' + action + query,
                {cache: 'no-store', signal: controller.signal, ...options});
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.error || 'Не удалось выполнить запрос');
            return data;
        } finally { clearTimeout(timeout); }
    }
    function renderList() {
        const search = $('search').value.trim().toLocaleLowerCase('ru');
        $('apartments').replaceChildren();
        const flats = (metadata?.flats || []).filter(flat => flat.number.includes(search) || flatTitle(flat).toLocaleLowerCase('ru').includes(search));
        flats.forEach(flat => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = selected?.id === flat.id ? 'selected' : '';
            const title = document.createElement('span'); title.className = 'apartment-name'; title.textContent = flatTitle(flat); button.append(title);
            const arrow = document.createElement('span'); arrow.className = 'apartment-arrow'; arrow.setAttribute('aria-hidden', 'true');
            arrow.textContent = selected?.id === flat.id ? '✓' : '→'; button.append(arrow);
            button.addEventListener('click', () => { if (busy) return; $('apartment').value = flat.number; select(flat); });
            $('apartments').append(button);
        });
        if (!flats.length) { const empty = document.createElement('p'); empty.className = 'empty'; empty.textContent = 'Ничего не найдено'; $('apartments').append(empty); }
    }
    function select(flat) {
        selected = flat;
        $('call').disabled = !flat || busy;
        if (!busy) status(flat ? flatTitle(flat) + ' · Готово к вызову' : 'Выберите, кому позвонить');
        renderList();
    }
    function inputChanged() {
        if (codeMode) {
            $('call').disabled = opening || !/^[1-9][0-9]{4}$/.test($('apartment').value) || Number($('apartment').value) < 10001;
            if (!opening) status('Введите код открытия двери');
            return;
        }
        const number = $('apartment').value.trim();
        const matches = (metadata?.flats || []).filter(flat => flat.number === number);
        select(matches.length === 1 ? matches[0] : null);
        if (number && matches.length !== 1) status(matches.length > 1 ? 'Выберите адресата из списка' : 'По этому номеру вызов недоступен');
    }
    function tab(list) {
        if (busy || opening) return;
        if (codeMode) setCodeMode(false);
        $('keypad-view').hidden = list; $('list-view').hidden = !list;
        [['keypad-tab', !list], ['list-tab', list]].forEach(([id, active]) => { $(id).classList.toggle('selected', active); $(id).setAttribute('aria-selected', String(active)); });
    }
    function setCodeMode(value) {
        if (busy || opening || !metadata) return;
        codeMode = value;
        $('code-mode').setAttribute('aria-pressed', String(value));
        $('code-mode').setAttribute('aria-label', value ? 'Вернуться к набору номера' : 'Ввести код открытия');
        $('control-title').textContent = value ? 'Код открытия двери' : idleControlTitle;
        $('apartment').type = value ? 'password' : 'text';
        $('apartment').maxLength = value ? 5 : 12;
        $('apartment').setAttribute('aria-label', value ? 'Код открытия двери' : idleControlTitle);
        $('apartment').value = '';
        $('call-label').textContent = value ? 'Открыть дверь' : 'Позвонить';
        $('call-icon').toggleAttribute('hidden', value); $('open-icon').toggleAttribute('hidden', !value);
        $('hint').textContent = value ? 'Используйте код, который набирают на домофоне.' : 'На звонок ответят в приложении и смогут открыть дверь.';
        selected = null; renderList(); inputChanged();
    }
    async function openByCode() {
        if (opening || busy || $('call').disabled) return;
        ++generation; doorOpened = false; opening = true;
        const code = $('apartment').value;
        $('apartment').value = ''; $('apartment').readOnly = true;
        $('call').disabled = true; $('code-mode').disabled = true;
        status('Отправляем команду открытия…');
        try {
            const result = await api('open-code', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ panel, code }) });
            doorMessage(result);
        } catch (error) {
            status(error.name === 'AbortError' ? 'Не удалось подтвердить открытие. Проверьте дверь перед повтором.' : error.message, 'error');
        } finally {
            opening = false; $('apartment').readOnly = false; $('code-mode').disabled = false;
        }
    }
    function setBusy(value) {
        busy = value;
        document.body.classList.toggle('busy', value);
        $('control-title').textContent = value && selected ? flatTitle(selected) : idleControlTitle;
        $('call').hidden = value; $('hangup').hidden = !value;
        $('call').disabled = !selected || value;
        $('apartment').readOnly = value;
    }
    function stopMedia() {
        preview?.stop(); preview = null;
        if (media) media.getTracks().forEach(track => track.stop());
        if (sourceMedia) sourceMedia.getTracks().forEach(track => track.stop());
        sourceMedia = media = null; $('preview').srcObject = null; $('remote-audio').srcObject = null;
        $('camera-empty').hidden = false; $('camera-label').textContent = 'Камера выключена';
        document.querySelector('.camera-label .dot').classList.remove('live');
        document.querySelector('.camera-panel').classList.remove('active');
        $('mute').hidden = true; $('mute').setAttribute('aria-pressed', 'false'); $('mute').setAttribute('aria-label', 'Выключить микрофон'); $('call-time').hidden = true;
    }
    function doorMessage(data) {
        if (data?.doorStatus === 'sent' && !doorOpened) {
            doorOpened = true;
            document.body.classList.add('door-open-visible');
            $('door-opened').showModal();
        }
        // A completed command remains visible across SIP hangup, failed final
        // polling or dismissal. Only a new call clears this confirmation.
        if (doorOpened) { status('Дверь открыта. Откройте дверь и заходите.', 'success'); return true; }
        const messages = { error: 'Не удалось подтвердить команду открытия двери', sending: 'Отправляем команду открытия…' };
        if (messages[data?.doorStatus]) { status(messages[data.doorStatus], data.doorStatus === 'error' ? 'error' : ''); return true; }
        return false;
    }
    async function finalDoorStatus(previous, attempt) {
        // The resident may hang up while the relay request is still running.
        // Briefly watch its result without retaining camera, audio or SIP.
        const deadline = Date.now() + 8000;
        while (Date.now() < deadline && generation === attempt && !call && !doorOpened) {
            await new Promise(resolve => setTimeout(resolve, 500));
            if (generation !== attempt || call || doorOpened || Date.now() >= deadline) return;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), Math.min(2000, deadline - Date.now()));
            try {
                const data = await api('status', { headers: { Authorization: 'Bearer ' + previous.token }, signal: controller.signal }, '?id=' + previous.id);
                if (generation !== attempt || call) return;
                doorMessage(data);
                if (['sent', 'error'].includes(data.doorStatus)) return;
            } catch (_) { /* Never turn an absent confirmation into success. */ }
            finally { clearTimeout(timeout); }
        }
    }
    async function finish(message, cancel = false) {
        if (ending) return;
        ending = true;
        const attempt = ++generation;
        clearTimeout(statusTimer); clearTimeout(frameTimer); clearInterval(durationTimer);
        clearTimeout(setupTimer); clearTimeout(iceTimer);
        iceTimer = null;
        const session = sipSession; sipSession = null;
        try { if (session && !session.isEnded()) session.terminate(); } catch (_) { }
        try { ua?.stop(); } catch (_) { }
        ua = null; stopMedia();
        const previous = call;
        let last;
        if (previous) {
            try {
                if (cancel) await api('cancel', { method: 'POST', headers: headers() }, '?id=' + previous.id);
                last = await api('status', { headers: headers() }, '?id=' + previous.id);
            } catch (_) { /* Keep the call's visible failure, never infer that a door opened. */ }
        }
        call = null; setBusy(false);
        if (!doorMessage(last)) status(message || 'Вызов завершён');
        ending = false;
        if (previous && !doorOpened && last?.doorStatus !== 'error') finalDoorStatus(previous, attempt);
    }
    async function sendFrame() {
        if (!call || !media || ending) return;
        const current = call;
        if (preview && !preview.canvas.hidden) {
            const canvas = document.createElement('canvas');
            canvas.width = 480; canvas.height = 270;
            const context = canvas.getContext('2d'); context.drawImage(preview.canvas, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .7));
            if (blob && call === current && !ending) {
                await api('frame', { method: 'POST', headers: { ...headers(), 'Content-Type': 'image/jpeg' }, body: blob }, '?id=' + current.id);
            }
        }
    }
    async function frameLoop() {
        try { await sendFrame(); } catch (_) { /* SIP video is independent of optional JPEG previews. */ }
        if (call && !ending) frameTimer = setTimeout(frameLoop, 1100);
    }
    async function poll() {
        if (!call || ending) return;
        const current = call;
        try {
            const data = await api('status', { headers: headers() }, '?id=' + current.id);
            if (call !== current || ending) return;
            if (!doorMessage(data)) {
                if (data.status === 'ringing') status('Звоним: ' + flatTitle(selected) + '…');
                if (data.status === 'answered') status('Вы на связи', 'success');
            }
            if (['ended', 'cancelled', 'failed'].includes(data.status)) { await finish(data.reason === 'NOANSWER' ? 'На звонок не ответили. Попробуйте позже.' : 'Вызов завершён'); return; }
        } catch (_) { /* A transient HTTP error does not terminate a healthy SIP call. */ }
        if (call === current && !ending) statusTimer = setTimeout(poll, 1000);
    }
    async function startCall() {
        if (codeMode) return openByCode();
        if (busy || !selected) return;
        const attempt = ++generation, flat = selected;
        doorOpened = false;
        if ($('door-opened').open) $('door-opened').close();
        const active = () => attempt === generation && busy && !ending;
        setBusy(true); status('Разрешите доступ к камере и микрофону…');
        try {
            if (!navigator.mediaDevices?.getUserMedia) throw new Error('Откройте страницу в браузере с поддержкой камеры');
            const acquired = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true }, video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 }, frameRate: { ideal: 15, max: 24 } } });
            if (!active()) { acquired.getTracks().forEach(track => track.stop()); return; }
            sourceMedia = acquired;
            // Decode the raw camera once, then use one centered 16:9 crop for
            // local preview, notification JPEGs and SIP. Keep audio untouched.
            $('preview').srcObject = new MediaStream(sourceMedia.getVideoTracks());
            await $('preview').play();
            if (!active()) return;
            preview = window.VirtualIntercomPreview.start($('preview'), $('preview-canvas'));
            media = new MediaStream([...sourceMedia.getAudioTracks(), ...preview.stream.getVideoTracks()]);
            $('camera-empty').hidden = true; $('camera-label').textContent = 'Ваша камера'; $('mute').hidden = false;
            document.querySelector('.camera-panel').classList.add('active');
            document.querySelector('.camera-label .dot').classList.add('live');
            const created = await api('session', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ panel, flatId: flat.id }) });
            if (!active()) {
                await api('cancel', { method: 'POST', headers: { Authorization: 'Bearer ' + created.token } }, '?id=' + created.id);
                return;
            }
            call = created;
            // The notification preview is optional and can upload while WSS,
            // ICE and SIP are connecting. It must not hold up the call.
            sendFrame().catch(() => {});
            if (!active()) return;
            status('Устанавливаем соединение…');
            setupTimer = setTimeout(() => { if (active()) finish('Не удалось установить соединение. Попробуйте ещё раз.', true); }, 25000);
            JsSIP.debug.disable('JsSIP:*');
            ua = new JsSIP.UA({ sockets: [new JsSIP.WebSocketInterface(call.sip.ws)], uri: 'sip:' + call.sip.username + '@' + call.sip.domain,
                password: call.sip.password, register: false, session_timers: false });
            ua.on('disconnected', () => { if (active()) finish('Связь прервалась. Попробуйте ещё раз.', true); });
            // The visitor only originates this dialog. Digest-authenticated
            // INVITE is sufficient; a separate REGISTER adds no capability.
            ua.on('connected', () => {
                if (!active() || !call || sipSession) return;
                sipSession = ua.call('sip:call@' + call.sip.domain, { mediaStream: media, mediaConstraints: { audio: true, video: true },
                    pcConfig: { iceServers: call.sip.iceServers }, rtcOfferConstraints: { offerToReceiveAudio: true, offerToReceiveVideo: true },
                    eventHandlers: { peerconnection: ({ peerconnection }) => {
                        // MediaStream.getTracks() has no guaranteed order. Safari
                        // can return video first; mobile SIP clients reject that
                        // SDP with 488. Reserve audio/video sections before JsSIP
                        // adds the outgoing tracks to these empty transceivers.
                        peerconnection.addTransceiver('audio', { direction: 'sendrecv' });
                        peerconnection.addTransceiver('video', { direction: 'sendrecv' });
                    } } });
                const current = sipSession;
                const relayedMedia = new Set();
                // SIP uses a complete SDP offer. Some interfaces never finish ICE
                // gathering; send collected candidates after a bounded grace period.
                current.on('icecandidate', event => {
                    if (!iceTimer) iceTimer = setTimeout(() => {
                        if (active() && current.connection.iceGatheringState === 'gathering') event.ready();
                    }, 2500);
                    // Once TURN has candidates for every media section, the
                    // offer already has a usable fallback for restrictive NAT.
                    // Do not wait for the remaining unused network interfaces.
                    if (event.candidate?.type === 'relay') {
                        relayedMedia.add(event.candidate.sdpMLineIndex);
                        if (relayedMedia.size >= current.connection.getTransceivers().length) {
                            clearTimeout(iceTimer);
                            iceTimer = setTimeout(() => {
                                if (active() && current.connection.iceGatheringState === 'gathering') event.ready();
                            }, 100);
                        }
                    }
                });
                const addAudio = event => {
                    if (event.track.kind !== 'audio') return;
                    $('remote-audio').srcObject = event.streams[0] || new MediaStream([event.track]);
                    $('remote-audio').play().catch(() => status('Нажмите на экран, чтобы включить звук'));
                };
                current.connection.addEventListener('track', addAudio);
                current.on('progress', () => { if (active()) { clearTimeout(setupTimer); status('Звоним: ' + flatTitle(flat) + '…'); } });
                current.on('confirmed', () => {
                    if (!active()) return;
                    clearTimeout(setupTimer);
                    status('Вы на связи', 'success'); startedAt = Date.now(); $('call-time').hidden = false;
                    durationTimer = setInterval(() => { const seconds = Math.floor((Date.now() - startedAt) / 1000); $('call-time').textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0'); }, 1000);
                });
                current.on('ended', () => { if (active()) finish('Вызов завершён'); });
                current.on('failed', event => { if (active()) finish(event.cause === 'Canceled' ? 'Вызов отменён' : event.cause === 'Busy' ? 'Абонент сейчас занят. Попробуйте позже.' : 'Не удалось соединиться. Попробуйте позже.', true); });
            });
            ua.start(); statusTimer = setTimeout(poll, 1000); frameTimer = setTimeout(frameLoop, 1100);
        } catch (error) {
            if (!active()) return;
            const messages = { NotAllowedError: 'Для вызова разрешите доступ к камере и микрофону в настройках браузера.', NotFoundError: 'Камера или микрофон не найдены.', NotReadableError: 'Камера занята другим приложением.' };
            await finish(messages[error.name] || error.message || 'Не удалось начать вызов', true);
            $('status').classList.add('error');
        }
    }
    document.querySelectorAll('[data-digit]').forEach(button => button.addEventListener('click', () => { if (busy || opening) return; if ($('apartment').value.length < $('apartment').maxLength) $('apartment').value += button.dataset.digit; inputChanged(); }));
    $('erase').addEventListener('click', () => { if (busy || opening) return; $('apartment').value = $('apartment').value.slice(0, -1); inputChanged(); });
    $('code-mode').addEventListener('click', () => setCodeMode(!codeMode));
    $('apartment').addEventListener('input', inputChanged); $('apartment').addEventListener('keydown', event => { if (event.key === 'Enter') startCall(); });
    $('search').addEventListener('input', renderList); $('keypad-tab').addEventListener('click', () => tab(false)); $('list-tab').addEventListener('click', () => tab(true));
    $('door-opened-dismiss').addEventListener('click', () => $('door-opened').close());
    $('door-opened').addEventListener('close', () => document.body.classList.remove('door-open-visible'));
    $('call').addEventListener('click', startCall); $('hangup').addEventListener('click', () => finish('Вызов отменён', true));
    $('mute').addEventListener('click', () => { const track = media?.getAudioTracks()[0]; if (!track) return; track.enabled = !track.enabled; $('mute').setAttribute('aria-pressed', String(!track.enabled)); $('mute').setAttribute('aria-label', track.enabled ? 'Выключить микрофон' : 'Включить микрофон'); });
    document.addEventListener('click', () => { if ($('remote-audio').srcObject) $('remote-audio').play().catch(() => {}); });
    window.addEventListener('pagehide', () => {
        try { sipSession?.terminate(); } catch (_) { }
        if (call) fetch('/virtual-intercom/api/cancel?id=' + call.id, { method: 'POST', headers: headers(), keepalive: true }).catch(() => {});
        stopMedia();
    });
    api('panel', {}, '?panel=' + encodeURIComponent(panel)).then(data => {
        metadata = data; $('entrance-title').textContent = data.title; $('entrance-subtitle').textContent = data.subtitle;
        $('code-mode').disabled = false;
        document.title = data.title + ' · Виртуальный домофон'; $('selection-mode').hidden = !data.listEnabled; renderList();
    }).catch(error => { $('entrance-subtitle').textContent = 'Проверьте ссылку или отсканируйте QR-код у входа.'; status(error.message, 'error'); });
})();
