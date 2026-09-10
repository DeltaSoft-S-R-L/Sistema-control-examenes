import cors from "cors";
import "dotenv/config";
import express from "express";

const app = express();
const port = Number(process.env.PORT ?? 4000);

app.use(cors());
app.use(express.json());

app.get("/health", (_request, response) => {
  response.json({ status: "ok", service: "examenes-backend" });
});

app.listen(port, () => {
  console.log(`Backend listening on port ${port}`);
});
