import React from 'react';
import { Outlet } from 'react-router-dom';
import Navbar from './Navbar';
import Sidebar from './Sidebar';
import './Layout.css';

export default function Layout() {
  return (
    <div className="d-flex flex-column min-vh-100">
      <Navbar />

      <div className="d-flex flex-grow-1">
        <aside className="layout-sidebar">
          <Sidebar />
        </aside>

        <main className="layout-main flex-grow-1 bg-light-subtle">
          <Outlet />
        </main>
      </div>
    </div>
  );
}