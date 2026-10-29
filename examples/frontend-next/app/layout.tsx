import type { Metadata } from 'next';
import './globals.css';
export const metadata: Metadata = { title: 'Elang Notes — Your own little corner', description: 'A private home for your ideas, powered by Elang and Next.js.', robots: { index: false, follow: false }, icons: { icon: '/favicon.svg' } };
export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) { return <html lang="en"><body>{children}</body></html>; }
