(function (root) {
  "use strict";

  let socket = null;
  let inited = false;
  let opts = { url: "", ticket: "" };
  const readyCbs = [];                 // onReady 回调（每次 connect/reconnect 触发）
  const listeners = new Map();         // event -> Set(fn)，用于重连后重挂

  function loadScript(src) {
    return new Promise((resolve, reject) => {
      if (root.io) return resolve();   // 已加载
      const s = document.createElement("script");
      s.src = src;
      s.onload = () => resolve();
      s.onerror = () => reject(new Error("socket.io 加载失败"));
      document.head.appendChild(s);
    });
  }

  function attachAll() {
    if (!socket) return;
    for (const [evt, fns] of listeners) {
      for (const fn of fns) socket.on(evt, fn);
    }
  }

  const WSM = {
    /** 初始化（幂等）。opts: { url, ticket, onError } */
    init(o) {
      o = o || {};
      opts.url = o.url || opts.url;
      opts.ticket = o.ticket || opts.ticket || "";
      if (inited) return WSM;          // 复用已存在连接，避免重复握手
      inited = true;
      const base = String(opts.url || "").replace(/\/$/, "");
      loadScript(base + "/socket.io/socket.io.js")
        .then(() => {
          socket = root.io(opts.url, {
            transports: ["websocket", "polling"],
            auth: { ticket: opts.ticket || "" },
            reconnection: true,
            reconnectionDelay: 400,
            reconnectionDelayMax: 3000,
          });
          attachAll();                 // 先挂业务监听，避免漏首包
          socket.on("connect", () => { readyCbs.forEach(fn => { try { fn(socket); } catch (_) {} }); });
        })
        .catch((e) => { if (o.onError) o.onError(e); });
      return WSM;
    },

    /** 连接就绪（首连或重连）后触发；若已连接则立即执行一次 */
    onReady(fn) {
      if (typeof fn !== "function") return;
      readyCbs.push(fn);
      if (socket && socket.connected) { try { fn(socket); } catch (_) {} }
      return WSM;
    },

    on(evt, fn) {
      if (!listeners.has(evt)) listeners.set(evt, new Set());
      listeners.get(evt).add(fn);
      if (socket) socket.on(evt, fn);
      return WSM;
    },

    off(evt, fn) {
      const set = listeners.get(evt);
      if (set) set.delete(fn);
      if (socket) socket.off(evt, fn);
      return WSM;
    },

    emit(evt, data, cb) {
      if (socket) socket.emit(evt, data, cb);
      else if (cb) cb({ ok: false, reason: "连接未就绪" });
      return WSM;
    },

    getSocket() { return socket; },
    isConnected() { return !!(socket && socket.connected); },
  };

  root.WSM = WSM;
})(typeof window !== "undefined" ? window : this);
