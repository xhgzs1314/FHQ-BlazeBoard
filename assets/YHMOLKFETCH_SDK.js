(function () {
    let signerFunc = null;
    let signerExpire = 0;
    let loadingPromise = null;
    let isReady = false;
    let initPromise = null;
    let signatureCache = new Map();
    let pendingSignatures = new Map();
    let deviceCredentialCache = null;
    const DEVICE_SALT = 'browser_fuckyhbb_salt_v1_2026';
    const DEVICE_VALIDITY_HOURS = 24;
    let csrfToken = null;
    const HEADER_ENCRYPT_SALT = 'yhmolk_header_encrypt_v1';
    const PROTOCOL_VERSION = 1;
    let encryptionKeyCache = null;
    let encryptionKeyExpire = 0;
    async function getBrowserFingerprint() {
        if (typeof window === 'undefined' || typeof navigator === 'undefined') {
            throw new Error('此函数只能在浏览器环境中运行');
        }
        const fingerprintComponents = [];
        fingerprintComponents.push(navigator.userAgent);
        fingerprintComponents.push(`${screen.width}x${screen.height}x${screen.colorDepth}`);
        fingerprintComponents.push(screen.availWidth + 'x' + screen.availHeight);
        fingerprintComponents.push(Intl.DateTimeFormat().resolvedOptions().timeZone);
        fingerprintComponents.push(navigator.language);
        fingerprintComponents.push(navigator.languages?.join(',') || '');
        fingerprintComponents.push(navigator.hardwareConcurrency || 'unknown');
        fingerprintComponents.push(navigator.deviceMemory || 'unknown');
        fingerprintComponents.push(await getCanvasFingerprint());
        fingerprintComponents.push(await getWebGLFingerprint());
        fingerprintComponents.push(await getAudioFingerprint());
        fingerprintComponents.push(await getFontFingerprint());
        const fingerprintString = fingerprintComponents.join('|');
        return await hashString(fingerprintString);
    }

    function getCanvasFingerprint() {
        return new Promise((resolve) => {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = 200;
                canvas.height = 200;
                const ctx = canvas.getContext('2d');
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.fillStyle = '#f60';
                ctx.fillRect(0, 0, 100, 100);
                ctx.fillStyle = '#069';
                ctx.fillText('Device Credential Code', 10, 50);
                ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                ctx.fillRect(50, 50, 80, 80);
                ctx.beginPath();
                ctx.moveTo(150, 20);
                ctx.quadraticCurveTo(180, 60, 160, 100);
                ctx.stroke();
                const dataURL = canvas.toDataURL();
                resolve(dataURL.substring(dataURL.length - 100));
            } catch (e) {
                resolve('canvas_error');
            }
        });
    }

    function getWebGLFingerprint() {
        return new Promise((resolve) => {
            try {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                if (!gl) {
                    resolve('webgl_not_supported');
                    return;
                }
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                if (debugInfo) {
                    const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
                    const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
                    resolve(`${vendor}|${renderer}`);
                } else {
                    resolve('webgl_no_debug_info');
                }
            } catch (e) {
                resolve('webgl_error');
            }
        });
    }

    function getAudioFingerprint() {
        return new Promise((resolve) => {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) {
                    resolve('audio_not_supported');
                    return;
                }
                const context = new AudioContext();
                const oscillator = context.createOscillator();
                const analyser = context.createAnalyser();
                oscillator.connect(analyser);
                analyser.connect(context.destination);
                oscillator.frequency.value = 440;
                setTimeout(() => {
                    const bufferLength = analyser.frequencyBinCount;
                    const dataArray = new Uint8Array(bufferLength);
                    analyser.getByteFrequencyData(dataArray);
                    const fingerprint = Array.from(dataArray.slice(0, 50)).join(',');
                    oscillator.disconnect();
                    analyser.disconnect();
                    context.close();
                    resolve(fingerprint);
                }, 100);
            } catch (e) {
                resolve('audio_error');
            }
        });
    }

    function getFontFingerprint() {
        return new Promise((resolve) => {
            try {
                const testFonts = [
                    'Arial', 'Verdana', 'Times New Roman', 'Courier New',
                    'Georgia', 'Comic Sans MS', 'Trebuchet MS', 'Impact'
                ];
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const testString = 'abcdefghijklmnopqrstuvwxyz0123456789';
                const fontMetrics = testFonts.map(font => {
                    ctx.font = `16px ${font}`;
                    const metrics = ctx.measureText(testString);
                    return `${font}:${metrics.width}`;
                }).join('|');
                resolve(fontMetrics);
            } catch (e) {
                resolve('font_error');
            }
        });
    }

    async function hashString(str) {
        if (window.crypto && window.crypto.subtle) {
            const encoder = new TextEncoder();
            const data = encoder.encode(str);
            const hashBuffer = await window.crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        } else {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash |= 0;
            }
            return Math.abs(hash).toString(16);
        }
    }

    async function generateDeviceCredential(forceRefresh = false) {
        if (!forceRefresh && deviceCredentialCache && deviceCredentialCache.expiresAt > Date.now()) {
            return deviceCredentialCache;
        }
        if (typeof window === 'undefined' || !window.document || !window.navigator) {
            throw new Error('此API必须在浏览器环境中调用');
        }
        if (typeof process !== 'undefined' && process.versions && process.versions.node) {
            throw new Error('检测到Node.js环境，禁止生成凭证码');
        }
        const deviceFingerprint = await getBrowserFingerprint();
        const timestamp = Date.now();
        const expiresAt = timestamp + (DEVICE_VALIDITY_HOURS * 60 * 60 * 1000);
        const rawCode = `${deviceFingerprint}|${timestamp}|${DEVICE_SALT}`;
        const credentialCode = await hashString(rawCode);
        deviceCredentialCache = {
            credentialCode: credentialCode.substring(0, 32),
            timestamp: timestamp,
            expiresAt: expiresAt,
            validityHours: DEVICE_VALIDITY_HOURS,
            fingerprint: deviceFingerprint.substring(0, 16)
        };
        return deviceCredentialCache;
    }

    async function initSigner() {
        signerFunc = null;
        signerExpire = 0;
        clearSignatureCache();
        encryptionKeyCache = null;
        encryptionKeyExpire = 0;
        await generateDeviceCredential(true);
        const resp = await fetch('/api/authsign/gettoken.php');
        const data = await resp.json();
        if (data.status !== 'ok') {
            throw new Error('Loading failed.');
        }
        signerFunc = eval(data.signer);
        signerExpire = data.expire * 1000;
        csrfToken = data.csrft || null;
        if (data.encrypt_key) {
            const hex = data.encrypt_key;
            const bytes = new Uint8Array(hex.match(/.{1,2}/g).map(byte => parseInt(byte, 16)));
            encryptionKeyCache = await crypto.subtle.importKey(
                'raw',
                bytes,
                { name: 'AES-GCM' },
                false,
                ['encrypt']
            );
            encryptionKeyExpire = Date.now() + 60000;
        }

        isReady = true;
        return signerFunc;
    }

    function clearSignatureCache() {
        signatureCache.clear();
        pendingSignatures.clear();
    }

    function getCacheKey(data, url) {
        const key = { url: url, data: data };
        return JSON.stringify(key);
    }

    function isSignerValid() {
        return signerFunc && Date.now() < signerExpire;
    }

    async function getCachedSignature(data, url, retryCount = 0) {
        const cacheKey = getCacheKey(data, url);
        const maxRetries = 1;
        const cached = signatureCache.get(cacheKey);
        if (cached && Date.now() < cached.expireAt) {
            if (isSignerValid()) {
                return cached;
            } else {
                signatureCache.delete(cacheKey);
            }
        }
        if (pendingSignatures.has(cacheKey)) {
            return await pendingSignatures.get(cacheKey);
        }
        const promise = (async () => {
            try {
                let signer = await getSigner();
                if (!isSignerValid()) {
                    signer = await refreshSigner();
                }
                let sigResult;
                try {
                    sigResult = await signer(data);
                    if (typeof sigResult === 'string') {
                        sigResult = { signature: sigResult, algorithm: null };
                    }
                } catch (e) {
                    if (retryCount < maxRetries) {
                        const newSigner = await refreshSigner();
                        sigResult = await newSigner(data);
                        if (typeof sigResult === 'string') {
                            sigResult = { signature: sigResult, algorithm: null };
                        }
                    } else {
                        throw e;
                    }
                }
                const cacheExpire = Math.min(signerExpire - 5000, Date.now() + 30000);
                const cacheEntry = {
                    signature: sigResult.signature,
                    algorithm: sigResult.algorithm,
                    expireAt: cacheExpire
                };
                signatureCache.set(cacheKey, cacheEntry);
                return cacheEntry;
            } finally {
                pendingSignatures.delete(cacheKey);
            }
        })();
        pendingSignatures.set(cacheKey, promise);
        return await promise;
    }

    async function getSigner() {
        if (isSignerValid()) {
            return signerFunc;
        }
        if (loadingPromise) {
            return loadingPromise;
        }
        loadingPromise = initSigner();
        const result = await loadingPromise;
        loadingPromise = null;
        return result;
    }

    async function refreshSigner() {
        clearSignatureCache();
        loadingPromise = null;
        csrfToken = null;
        encryptionKeyCache = null;
        encryptionKeyExpire = 0;
        return initSigner();
    }

    async function ensureInit() {
        if (isReady && isSignerValid()) return;
        if (initPromise) return initPromise;
        initPromise = getSigner();
        await initPromise;
    }

    function generateNonce() {
        return Date.now().toString(36) + Math.random().toString(36).substring(2, 15) +
            Math.random().toString(36).substring(2, 15);
    }
    async function deriveEncryptionKey(dynamicSecret) {
        const encoder = new TextEncoder();
        const keyMaterial = await crypto.subtle.importKey(
            'raw',
            encoder.encode(dynamicSecret),
            'PBKDF2',
            false,
            ['deriveKey']
        );

        const encryptionKey = await crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: encoder.encode(HEADER_ENCRYPT_SALT),
                iterations: 10000,
                hash: 'SHA-256'
            },
            keyMaterial,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt', 'decrypt']
        );

        return encryptionKey;
    }

    async function getEncryptionKey() {
        if (encryptionKeyCache && encryptionKeyExpire > Date.now()) {
            return encryptionKeyCache;
        }
        await refreshSigner();
        return encryptionKeyCache;
    }
    function obfuscateHeaders(headers, userHeaders) {
        const headerKeys = Object.keys(headers).filter(key =>
            !key.startsWith('X-User-')
        );
        const values = headerKeys.map(key => headers[key]);
        const fieldMap = {};
        const shuffledKeys = [...headerKeys];
        for (let i = shuffledKeys.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffledKeys[i], shuffledKeys[j]] = [shuffledKeys[j], shuffledKeys[i]];
        }
        const newKeys = [];
        const usedNames = new Set();
        for (let i = 0; i < headerKeys.length; i++) {
            let newKey;
            do {
                const randomStr = Math.random().toString(36).substring(2, 10) +
                    Math.random().toString(36).substring(2, 10);
                newKey = 'X-' + randomStr;
            } while (usedNames.has(newKey));
            usedNames.add(newKey);
            newKeys.push(newKey);
            fieldMap[newKey] = headerKeys[i];
        }
        const shuffledValues = [...values];
        const shuffleOrder = [];
        for (let i = shuffledValues.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffledValues[i], shuffledValues[j]] = [shuffledValues[j], shuffledValues[i]];
            if (i !== j) {
                shuffleOrder.push({ from: i, to: j });
            }
        }
        const obfuscatedHeaders = {};
        for (let i = 0; i < newKeys.length; i++) {
            obfuscatedHeaders[newKeys[i]] = shuffledValues[i];
        }
        const meta = {
            version: PROTOCOL_VERSION,
            fieldMap: fieldMap,
            shuffleOrder: shuffleOrder,
            originalKeys: headerKeys,
            userHeaders: userHeaders || {}
        };

        return {
            obfuscatedHeaders: obfuscatedHeaders,
            meta: meta
        };
    }


    async function encryptHeaders(obfuscatedHeaders, meta, encryptionKey) {
        const payload = {
            version: PROTOCOL_VERSION,
            timestamp: Date.now(),
            headers: obfuscatedHeaders,
            meta: meta
        };

        const jsonStr = JSON.stringify(payload);
        const encoder = new TextEncoder();
        const data = encoder.encode(jsonStr);
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encrypted = await crypto.subtle.encrypt(
            {
                name: 'AES-GCM',
                iv: iv
            },
            encryptionKey,
            data
        );
        const combined = new Uint8Array(iv.length + encrypted.byteLength);
        combined.set(iv, 0);
        combined.set(new Uint8Array(encrypted), iv.length);

        return btoa(String.fromCharCode(...combined));
    }
    async function securedApiCallInternal(url, data, options = {}) {
        let signatureData = data;
        let bodyData = data;
        let contentType = options.contentType || 'application/json';
        let csrfParamName = options.csrfParamName || 'csrf_token';
        let skipCsrf = options.skipCsrf || false;
        if (contentType === 'application/json' && typeof data === 'object') {
            bodyData = JSON.stringify(data);
            signatureData = data;
        } else if (contentType === 'application/x-www-form-urlencoded' && typeof data === 'object') {
            bodyData = new URLSearchParams(data).toString();
            signatureData = data;
        } else if (typeof data === 'object') {
            bodyData = new URLSearchParams(data).toString();
            signatureData = data;
            contentType = 'application/x-www-form-urlencoded';
        } else {
            bodyData = data;
            signatureData = data;
        }
        const deviceCred = await generateDeviceCredential(false);
        const sigEntry = await getCachedSignature(signatureData, url);
        const timestamp = Math.floor(Date.now() / 1000);
        const nonce = generateNonce();
        const rawHeaders = {
            'X-API-Signature': sigEntry.signature,
            'X-API-Timestamp': timestamp.toString(),
            'X-API-Nonce': nonce,
            'X-Device-Credential': deviceCred.credentialCode,
            'X-Device-Timestamp': deviceCred.timestamp.toString(),
            'X-Device-Expires': deviceCred.expiresAt.toString(),
            'X-Device-Fingerprint': deviceCred.fingerprint
        };

        if (!skipCsrf && csrfToken) {
            rawHeaders['X-CSRF-Token'] = csrfToken;
        }
        if (sigEntry.algorithm) {
            rawHeaders['X-API-Algorithm'] = sigEntry.algorithm;
        }
        const userHeaders = options.headers || {};
        const { obfuscatedHeaders, meta } = obfuscateHeaders(rawHeaders, userHeaders);
        const encryptionKey = await getEncryptionKey();
        const encryptedPayload = await encryptHeaders(obfuscatedHeaders, meta, encryptionKey);
        const finalHeaders = {
            'X-Secure-Payload': encryptedPayload,
            'X-Protocol-Version': PROTOCOL_VERSION.toString(),
            'Content-Type': contentType,
            ...userHeaders
        };
        const response = await fetch(url, {
            method: options.method || 'POST',
            headers: finalHeaders,
            body: bodyData,
            credentials: 'same-origin'
        });
        if (!response.ok) {
            let errorData;
            try {
                errorData = await response.json();
            } catch (e) {
                errorData = { error: `HTTP ${response.status}` };
            }
            const errorMsg = errorData.error || errorData.message || '';
            if (errorMsg.includes('HEADER_DECRYPT_FAILED') ||
                errorMsg.includes('HEADER_MAC_INVALID') ||
                errorMsg.includes('PROTOCOL_VERSION_MISMATCH')) {
                await refreshSigner();
                return securedApiCallInternal(url, data, options);
            }
            const isDeviceExpired = errorMsg.includes('DEVICE_CREDENTIAL_EXPIRED') ||
                errorMsg.includes('device_credential_expired');
            const isSignatureError = errorMsg.includes('signature') ||
                errorMsg.includes('签名') ||
                errorMsg.includes('SESSION_EXPIRED');

            if (isDeviceExpired) {
                await generateDeviceCredential(true);
                return securedApiCallInternal(url, data, options);
            }

            if (isSignatureError) {
                await refreshSigner();
                const cacheKey = getCacheKey(signatureData, url);
                signatureCache.delete(cacheKey);
                return securedApiCallInternal(url, data, options);
            }

            throw new Error(errorMsg || `HTTP ${response.status}`);
        }

        const result = await response.json();
        return result;
    }
    let currentCallPromise = null;
    window.yhmolk_fetchpull = async function (url, data, options = {}) {
        await ensureInit();
        if (currentCallPromise) {
            await currentCallPromise;
        }
        const callPromise = (async () => {
            try {
                return await securedApiCallInternal(url, data, options);
            } finally {
                if (currentCallPromise === callPromise) {
                    currentCallPromise = null;
                }
            }
        })();

        currentCallPromise = callPromise;
        return callPromise;
    };

    window.clearSignatureCache = clearSignatureCache;

    window.refreshSignerManually = async function () {
        await generateDeviceCredential(true);
        return refreshSigner();
    };

    window.getDeviceCredential = async function (forceRefresh = false) {
        return generateDeviceCredential(forceRefresh);
    };

    window.destroyyhmolk_pullapi = function () {
        clearSignatureCache();
        signerFunc = null;
        signerExpire = 0;
        loadingPromise = null;
        isReady = false;
        initPromise = null;
        currentCallPromise = null;
        deviceCredentialCache = null;
        encryptionKeyCache = null;
        encryptionKeyExpire = 0;
        delete window.yhmolk_fetchpull;
        delete window.clearSignatureCache;
        delete window.refreshSignerManually;
        delete window.getDeviceCredential;
        delete window.destroyyhmolk_pullapi;
    };
})();