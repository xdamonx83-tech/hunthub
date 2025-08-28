import { createServer } from "http";
import { Server } from "socket.io";
import jwt from "jsonwebtoken";
import mysql from "mysql2/promise";

// ==== ENV / KONFIG anpassen ====
const PORT      = process.env.PORT || 3001;
const JWT_SECRET= process.env.WS_JWT_SECRET || "UNBEDINGT_EIGENEN_LANGEN_GEHEIMEN_SCHLUESSEL_SETZEN";
const DB = {
  host: process.env.DB_HOST || "127.0.0.1",
  user: process.env.DB_USER || "dbuser",
  password: process.env.DB_PASS || "dbpass",
  database: process.env.DB_NAME || "htda_db14",
  waitForConnections: true,
  connectionLimit: 10
};

// ==== DB-Pool ====
const pool = mysql.createPool(DB);

// ==== HTTP + Socket.IO ====
const httpServer = createServer();
const io = new Server(httpServer, {
  // gleiche Origin via Apache-Proxy
  cors: { origin: ["https://hunthub.online"], methods: ["GET","POST"], credentials: true },
  path: "/socket.io"
});

// ==== Auth pro Verbindung ====
io.use((socket, next) => {
  const token = socket.handshake.auth?.token;
  if (!token) return next(new Error("no_token"));
  try {
    const payload = jwt.verify(token, JWT_SECRET);
    socket.userId = parseInt(payload.sub, 10);
    if (!socket.userId) return next(new Error("bad_token"));
    return next();
  } catch (e) {
    return next(new Error("bad_token"));
  }
});

io.on("connection", (socket) => {
  const me = socket.userId;
  socket.join(`user:${me}`);

  // Client kann optional letzte ID schicken, um „verpasste“ nachzuholen
  socket.on("hello", async (data={}, ack) => {
    try {
      const sinceId = Number(data.since_id || 0);
      const [rows] = await pool.query(
        "SELECT id,sender_id,recipient_id,body,created_at FROM messages WHERE (sender_id=? OR recipient_id=?) AND id> ? ORDER BY id ASC LIMIT 200",
        [me, me, sinceId]
      );
      ack?.({ ok:true, messages: rows, last_id: rows.length ? rows[rows.length-1].id : sinceId });
    } catch (e) { ack?.({ ok:false, error:"sql" }); }
  });

  // Nachricht senden
  socket.on("message:send", async (data, ack) => {
    try {
      const to   = parseInt(data?.to, 10);
      let body   = String(data?.body || "").trim();
      if (!to || to === me) return ack?.({ ok:false, error:"invalid_target" });
      if (!body)           return ack?.({ ok:false, error:"empty" });
      if (body.length > 4000) body = body.slice(0, 4000);

      const [res] = await pool.query(
        "INSERT INTO messages (sender_id, recipient_id, body) VALUES (?, ?, ?)",
        [me, to, body]
      );
      const id = res.insertId;
      const [rows] = await pool.query(
        "SELECT id,sender_id,recipient_id,body,created_at FROM messages WHERE id=?",
        [id]
      );
      const msg = rows[0];

      // an Sender (Ack) + Empfänger pushen
      ack?.({ ok:true, message: msg });
      io.to(`user:${to}`).emit("message:new", msg);
    } catch (e) {
      ack?.({ ok:false, error:"sql" });
    }
  });

  // Gelesen markieren
  socket.on("message:read_upto", async (data={}, ack) => {
    try {
      const otherId = parseInt(data.user_id, 10);
      const upToId  = parseInt(data.up_to_id, 10);
      if (!otherId || !upToId) return ack?.({ ok:false, error:"bad_params" });

      const [res] = await pool.query(
        `UPDATE messages
           SET read_at = IF(read_at IS NULL, NOW(), read_at)
         WHERE recipient_id=? AND sender_id=? AND id <= ? AND read_at IS NULL`,
        [me, otherId, upToId]
      );
      ack?.({ ok:true, updated: res.affectedRows });
    } catch (e) { ack?.({ ok:false, error:"sql" }); }
  });

  socket.on("disconnect", () => {});
});

httpServer.listen(PORT, () => {
  console.log("WS listening on", PORT);
});
