import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Sistema de Control de Exámenes",
  description: "Gestión y seguimiento de exámenes académicos."
};

export default function RootLayout({
  children
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="es">
      <body>{children}</body>
    </html>
  );
}
