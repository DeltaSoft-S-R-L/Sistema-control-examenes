import "./utils/bigint-serializer";
import cors from "cors";
import "dotenv/config";
import express from "express";
import authRoutes from "./routes/auth.routes";
import userRoutes from "./routes/user.routes";
import { errorHandler } from "./middlewares/error.middleware";

const app = express();
const port = Number(process.env.PORT ?? 4000);

// Middlewares globales
app.use(cors());
app.use(express.json());

// Endpoint de verificación de salud
app.get("/health", (_request, response) => {
  response.json({ status: "ok", service: "examenes-backend" });
});

// Rutas de la API
app.use("/api/auth", authRoutes);
app.use("/api/users", userRoutes);

// Middleware centralizado de manejo de errores (debe ser el último)
app.use(errorHandler);

app.listen(port, () => {
  console.log(`Backend listening on port ${port}`);
});

export default app;
