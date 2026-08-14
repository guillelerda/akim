<style>
.adm-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.adm-stat  {
    background: #111827; border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px; padding: 1.25rem 1.5rem;
    display: flex; align-items: center; gap: 1rem;
}
.adm-stat-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.adm-stat-icon.blue   { background: rgba(37,99,235,.15);  color: #60a5fa; }
.adm-stat-icon.amber  { background: rgba(217,119,6,.15);  color: #fbbf24; }
.adm-stat-icon.green  { background: rgba(22,163,74,.15);  color: #4ade80; }
.adm-stat-icon.purple { background: rgba(124,58,237,.15); color: #a78bfa; }
.adm-stat-icon.cyan   { background: rgba(6,182,212,.15);  color: #67e8f9; }
.adm-stat-val  { font-size: 1.75rem; font-weight: 700; line-height: 1; }
.adm-stat-lbl  { font-size: .72rem; color: #6b7280; margin-top: .2rem; }

.adm-quick-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;
}
.adm-quick-btn {
    display: flex; align-items: center; gap: .85rem;
    background: #111827; border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px; padding: 1rem 1.25rem;
    text-decoration: none; color: #d1d5db;
    font-size: .85rem; font-weight: 500;
    transition: border-color .2s, background .2s;
    cursor: pointer;
}
.adm-quick-btn:hover {
    border-color: rgba(37,99,235,.3);
    background: rgba(37,99,235,.06);
    color: #fff;
}
.adm-quick-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
</style>

<div class="adm-card">
    <div class="adm-card-title">Resumen de la plataforma</div>
    <div class="adm-stats">

        <div class="adm-stat">
            <div class="adm-stat-icon blue">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
            </div>
            <div>
                <div class="adm-stat-val"><?php echo $counts['empresas'] ?? '—'; ?></div>
                <div class="adm-stat-lbl">Empresas</div>
            </div>
        </div>

        <div class="adm-stat">
            <div class="adm-stat-icon amber">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div>
                <div class="adm-stat-val"><?php echo $counts['licencias'] ?? '—'; ?></div>
                <div class="adm-stat-lbl">Licencias activas</div>
            </div>
        </div>

        <div class="adm-stat">
            <div class="adm-stat-icon green">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <div class="adm-stat-val"><?php echo $counts['usuarios'] ?? '—'; ?></div>
                <div class="adm-stat-lbl">Usuarios</div>
            </div>
        </div>

        <div class="adm-stat">
            <div class="adm-stat-icon purple">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
            </div>
            <div>
                <div class="adm-stat-val"><?php echo $counts['bases'] ?? '—'; ?></div>
                <div class="adm-stat-lbl">Bases de datos</div>
            </div>
        </div>

        <div class="adm-stat">
            <div class="adm-stat-icon cyan">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div>
                <div class="adm-stat-val"><?php echo $counts['modulos'] ?? '—'; ?></div>
                <div class="adm-stat-lbl">Módulos</div>
            </div>
        </div>

    </div>
</div>

<div class="adm-card">
    <div class="adm-card-title">Accesos rápidos</div>
    <div class="adm-quick-grid">

        <a class="adm-quick-btn" href="adm_empresas">
            <div class="adm-quick-icon" style="background:rgba(37,99,235,.12);color:#60a5fa">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            Gestionar empresas
        </a>

        <a class="adm-quick-btn" href="adm_usuarios">
            <div class="adm-quick-icon" style="background:rgba(22,163,74,.12);color:#4ade80">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            Gestionar usuarios
        </a>

        <a class="adm-quick-btn" href="adm_licencias">
            <div class="adm-quick-icon" style="background:rgba(217,119,6,.12);color:#fbbf24">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            Gestionar licencias
        </a>

        <a class="adm-quick-btn" href="adm_bases">
            <div class="adm-quick-icon" style="background:rgba(124,58,237,.12);color:#a78bfa">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
            </div>
            Bases de datos
        </a>

        <a class="adm-quick-btn" href="adm_modulos">
            <div class="adm-quick-icon" style="background:rgba(6,182,212,.12);color:#67e8f9">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            Módulos del sistema
        </a>

    </div>
</div>
