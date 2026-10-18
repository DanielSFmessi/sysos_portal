<?php
// Extracción robusta de métricas del Kernel para el usuario www-data
$raw_uptime = shell_exec('/usr/bin/uptime 2>&1') ?? '';
if (empty(trim($raw_uptime))) {
    // Respaldo directo leyendo el pseudo-sistema de archivos /proc
    $uptime_sec = file_exists('/proc/uptime') ? (int)explode(' ', file_get_contents('/proc/uptime'))[0] : 0;
    $raw_uptime = "up " . gmdate("H:i:s", $uptime_sec) . ", load average: " . (@file_get_contents('/proc/loadavg') ?: '0.00 0.00 0.00');
}

$raw_free = shell_exec('/usr/bin/free -m 2>&1') ?? '';
$raw_disk = shell_exec('/bin/df -h / 2>&1') ?? '';
$raw_top_procs = shell_exec('/bin/ps aux --sort=-%cpu 2>&1 | /usr/bin/head -n 11') ?? '';

// Parseo de Memoria RAM
$ram_total = $ram_used = $ram_free = $ram_percent = 0;
if (preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $raw_free, $m)) {
    $ram_total = (int)$m[1];
    $ram_used = (int)$m[2];
    $ram_free = (int)$m[3];
    $ram_percent = $ram_total > 0 ? round(($ram_used / $ram_total) * 100) : 0;
} elseif (file_exists('/proc/meminfo')) {
    // Respaldo desde /proc/meminfo si free falla
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mt);
    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $ma);
    $ram_total = isset($mt[1]) ? round((int)$mt[1] / 1024) : 0;
    $ram_avail = isset($ma[1]) ? round((int)$ma[1] / 1024) : 0;
    $ram_used = $ram_total - $ram_avail;
    $ram_percent = $ram_total > 0 ? round(($ram_used / $ram_total) * 100) : 0;
}

// Parseo de Disco
$disk_percent = 0;
if (preg_match('/(\d+)%\s+\//', $raw_disk, $d)) {
    $disk_percent = (int)$d[1];
}

// Extracción de Cargas de CPU
$cpu_loads = "0.00, 0.00, 0.00";
if (preg_match('/load average[s]?:\s*(.*)$/i', $raw_uptime, $l)) {
    $cpu_loads = trim($l[1]);
} elseif (file_exists('/proc/loadavg')) {
    $load_parts = explode(' ', file_get_contents('/proc/loadavg'));
    $cpu_loads = "{$load_parts[0]}, {$load_parts[1]}, {$load_parts[2]}";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KernelTech // SysOS Neural Core</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Rajdhani:wght@500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-void: #05070f;
            --bg-card: rgba(13, 19, 36, 0.7);
            --border-neon: rgba(0, 240, 255, 0.25);
            --border-magenta: rgba(255, 0, 128, 0.35);
            --neon-cyan: #00f0ff;
            --neon-magenta: #ff007f;
            --neon-purple: #9d4edd;
            --neon-green: #00ff66;
            --text-glow: #e2e8f0;
            --text-muted: #64748b;
            --font-display: 'Orbitron', monospace;
            --font-ui: 'Rajdhani', sans-serif;
            --font-code: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; scroll-behavior: smooth; }

        body {
            background-color: var(--bg-void);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(0, 240, 255, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(255, 0, 128, 0.08) 0%, transparent 40%),
                linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
            color: var(--text-glow);
            font-family: var(--font-ui);
            font-size: 18px;
            display: flex;
            min-height: 100vh;
        }

        aside {
            width: 290px;
            background: rgba(8, 12, 24, 0.85);
            backdrop-filter: blur(16px);
            border-right: 1px solid var(--border-neon);
            padding: 28px 22px;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 28px;
            z-index: 100;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .core-orb {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-magenta));
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.5);
            display: grid;
            place-items: center;
            font-family: var(--font-display);
            font-weight: 900;
            color: #000;
            animation: pulse-glow 3s infinite alternate;
        }

        @keyframes pulse-glow {
            0% { box-shadow: 0 0 15px rgba(0, 240, 255, 0.4); transform: scale(0.98); }
            100% { box-shadow: 0 0 28px rgba(255, 0, 128, 0.7); transform: scale(1.02); }
        }

        .brand-text h2 {
            font-family: var(--font-display);
            font-size: 1.05rem;
            letter-spacing: 0.12em;
            color: #fff;
            text-transform: uppercase;
        }

        .brand-text span {
            font-size: 0.82rem;
            color: var(--neon-cyan);
            letter-spacing: 0.18em;
            font-weight: 700;
        }

        .ai-status-pill {
            background: rgba(0, 240, 255, 0.05);
            border: 1px solid var(--neon-cyan);
            padding: 8px 12px;
            border-radius: 6px;
            font-family: var(--font-code);
            font-size: 0.75rem;
            color: var(--neon-cyan);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: inset 0 0 10px rgba(0, 240, 255, 0.1);
        }

        .blinking-led {
            width: 8px;
            height: 8px;
            background: var(--neon-cyan);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--neon-cyan);
            animation: blink 1.2s infinite ease-in-out;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.2; }
        }

        nav { display: flex; flex-direction: column; gap: 8px; }
        .nav-category {
            font-family: var(--font-display);
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            color: var(--neon-magenta);
            text-transform: uppercase;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid transparent;
            transition: all 0.25s ease;
        }

        nav a:hover, nav a.active {
            background: rgba(0, 240, 255, 0.07);
            border-color: var(--border-neon);
            color: #fff;
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.15);
            transform: translateX(4px);
        }

        main {
            margin-left: 290px;
            flex: 1;
            padding: 36px 52px;
            max-width: 1350px;
        }

        header.telemetry-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--border-neon);
            margin-bottom: 38px;
        }

        .title-unit h1 {
            font-family: var(--font-display);
            font-size: 1.85rem;
            letter-spacing: 0.06em;
            background: linear-gradient(90deg, #fff, var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .title-unit p {
            color: var(--text-muted);
            font-size: 0.92rem;
            margin-top: 4px;
            font-family: var(--font-code);
        }

        .quantum-badge {
            background: rgba(255, 0, 128, 0.1);
            border: 1px solid var(--border-magenta);
            padding: 8px 18px;
            border-radius: 9999px;
            font-family: var(--font-code);
            font-size: 0.82rem;
            color: var(--neon-magenta);
            box-shadow: 0 0 12px rgba(255, 0, 128, 0.2);
        }

        .metrics-hud {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 22px;
            margin-bottom: 40px;
        }

        .hud-card {
            background: var(--bg-card);
            border: 1px solid var(--border-neon);
            border-radius: 12px;
            padding: 22px;
            backdrop-filter: blur(12px);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .hud-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 40px; height: 2px;
            background: var(--neon-cyan);
            box-shadow: 0 0 10px var(--neon-cyan);
        }

        .hud-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: var(--font-display);
            font-size: 0.78rem;
            color: var(--neon-cyan);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .hud-value {
            font-family: var(--font-display);
            font-size: 1.8rem;
            margin: 12px 0 6px 0;
            color: #fff;
            text-shadow: 0 0 12px rgba(255, 255, 255, 0.3);
        }

        .progress-hud {
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin: 10px 0 12px 0;
        }

        .progress-fill-cyan {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
            box-shadow: 0 0 10px var(--neon-cyan);
        }

        .progress-fill-magenta {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-magenta), var(--neon-purple));
            box-shadow: 0 0 10px var(--neon-magenta);
        }

        .cyber-terminal {
            background: #020409;
            border: 1px solid var(--border-neon);
            border-radius: 8px;
            padding: 14px 18px;
            font-family: var(--font-code);
            font-size: 0.78rem;
            color: var(--neon-cyan);
            line-height: 1.5;
            white-space: pre;
            overflow-x: auto;
            box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.8);
        }

        .section-header-ai {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 44px 0 20px 0;
        }

        .section-header-ai h2 {
            font-family: var(--font-display);
            font-size: 1.45rem;
            letter-spacing: 0.08em;
            color: #fff;
        }

        .section-header-ai .cyber-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--border-neon), transparent);
        }

        .module-matrix {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 30px;
            margin-bottom: 26px;
            position: relative;
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }

        .module-matrix:hover {
            border-color: var(--border-neon);
            box-shadow: 0 10px 30px rgba(0, 240, 255, 0.08);
        }

        .module-matrix h3 {
            font-family: var(--font-display);
            font-size: 1.25rem;
            letter-spacing: 0.05em;
            color: var(--neon-cyan);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .module-matrix p {
            color: #cbd5e1;
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .ai-neural-box {
            background: rgba(0, 240, 255, 0.03);
            border-left: 3px solid var(--neon-cyan);
            border-radius: 0 10px 10px 0;
            padding: 18px 22px;
            margin-top: 18px;
        }

        .ai-neural-box strong {
            display: block;
            font-family: var(--font-display);
            font-size: 0.88rem;
            color: var(--neon-cyan);
            letter-spacing: 0.08em;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .ai-neural-box span {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.6;
            display: block;
        }

        .holo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            margin-top: 18px;
        }

        .holo-table th {
            font-family: var(--font-display);
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 14px 18px;
            background: rgba(0, 240, 255, 0.05);
            color: var(--neon-cyan);
            border-bottom: 2px solid var(--border-neon);
            text-align: left;
        }

        .holo-table td {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
        }

        .holo-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
            color: #fff;
        }
    </style>
</head>
<body>

    <aside>
        <div class="brand-box">
            <div class="core-orb">KT</div>
            <div class="brand-text">
                <h2>KernelTech</h2>
                <span>NEURAL // SYSOS</span>
            </div>
        </div>

        <div class="ai-status-pill">
            <div class="blinking-led"></div>
            <span>KERNEL CORE // ONLINE</span>
        </div>

        <nav>
            <div class="nav-category">Telemetría Viva</div>
            <a href="#telemetry" class="active">Host Diagnostics</a>
            <a href="#process-tree">Active Threads</a>

            <div class="nav-category">Base de Conocimiento</div>
            <a href="#axis1">01 // Procesos & Mutex</a>
            <a href="#axis2">02 // Memoria Virtual</a>
            <a href="#axis3">03 // VFS & E/S</a>
            <a href="#axis4">04 // KVM vs Docker</a>
        </nav>
    </aside>

    <main>
        <header class="telemetry-bar">
            <div class="title-unit">
                <h1>SysOS AI Diagnostic Engine</h1>
                <p>HOST: sysos.local &bull; UBUNTU 26.04 LTS &bull; KERNEL ARCHITECTURE ACTIVE</p>
            </div>
            <div class="quantum-badge">
                SYS_LOAD: <?= htmlspecialchars($cpu_loads) ?>
            </div>
        </header>

        <section id="telemetry">
            <div class="metrics-hud">
                <div class="hud-card">
                    <div class="hud-header">
                        <span>CPU Uptime & Nodes</span>
                        <span>[CORE-01]</span>
                    </div>
                    <div class="hud-value" style="font-size: 1.35rem; color: var(--neon-cyan);">
                        <?= htmlspecialchars(explode(',', $raw_uptime)[0]) ?>
                    </div>
                    <div class="cyber-terminal"><?= htmlspecialchars($raw_uptime) ?></div>
                </div>

                <div class="hud-card">
                    <div class="hud-header">
                        <span>RAM Memory Allocation</span>
                        <span style="color: var(--neon-magenta);"><?= $ram_percent ?>%</span>
                    </div>
                    <div class="hud-value"><?= $ram_used ?> <span style="font-size: 0.9rem; color: var(--text-muted);">/ <?= $ram_total ?> MB</span></div>
                    <div class="progress-hud">
                        <div class="progress-fill-magenta" style="width: <?= $ram_percent ?>%;"></div>
                    </div>
                    <div class="cyber-terminal"><?= htmlspecialchars($raw_free) ?></div>
                </div>

                <div class="hud-card">
                    <div class="hud-header">
                        <span>Root Partition Block (/)</span>
                        <span style="color: var(--neon-cyan);"><?= $disk_percent ?>%</span>
                    </div>
                    <div class="hud-value"><?= $disk_percent ?>% <span style="font-size: 0.9rem; color: var(--text-muted);">Allocated</span></div>
                    <div class="progress-hud">
                        <div class="progress-fill-cyan" style="width: <?= $disk_percent ?>%;"></div>
                    </div>
                    <div class="cyber-terminal"><?= htmlspecialchars($raw_disk) ?></div>
                </div>
            </div>

            <div class="module-matrix" id="process-tree">
                <h3>Top 10 High-Compute Processes (Kernel Priority)</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Comando ejecutado: <code>ps aux --sort=-%cpu | head -n 11</code></p>
                <div class="cyber-terminal" style="font-size: 0.74rem;"><?= htmlspecialchars($raw_top_procs) ?></div>
            </div>
        </section>

        <div class="section-header-ai">
            <h2>Deep Neural Syllabus // Sistemas Operativos</h2>
            <div class="cyber-line"></div>
        </div>

        <div class="module-matrix" id="axis1">
            <h3>01 // Gestión de Procesos, Concurrencia y Sincronización</h3>
            <p>Un <strong>proceso</strong> representa una unidad de ejecución aislada dotada de su propio bloque de control (PCB) y espacio de memoria virtual direccionable. Los <strong>hilos (POSIX Threads)</strong> comparten recursos y mapa de memoria dentro del mismo proceso, maximizando la concurrencia a costa de requerir control riguroso de sincronización.</p>
            <p>El planificador del Kernel arbitra los estados del ciclo de vida (<em>New &rarr; Ready &rarr; Running &rarr; Waiting &rarr; Terminated</em>) implementando políticas deterministas como <strong>Round Robin</strong> (distribución equitativa por ráfagas de tiempo), <strong>SJF</strong> (reducción de latencia teórica) o algoritmos de <strong>Prioridades con Aging</strong> para erradicar la inanición (starvation). Cuando múltiples hilos leen y escriben sobre la misma dirección sin control, se detona una <strong>condición de carrera</strong>, neutralizada a través de primitivas atómicas de sincronización como <strong>Mutexes binarios</strong> y <strong>Semáforos de conteo</strong>.</p>
            <div class="ai-neural-box">
                <strong>Simulación de Caso Práctico: Problema del Productor-Consumidor</strong>
                <span>Implementación de un buffer acotado circular sincronizado mediante tres semáforos POSIX: un mutex binario para la sección crítica, un semáforo contador de <em>espacios_libres</em> inicializado en N, y un semáforo de <em>elementos_ocupados</em> en 0. Previene desbordamiento y garantiza consumo ordenado sin bloqueos mutuos (Deadlock).</span>
            </div>
        </div>

        <div class="module-matrix" id="axis2">
            <h3>02 // Gestión de Memoria Virtual & Paginación</h3>
            <p>La arquitectura de <strong>memoria virtual</strong> proporciona a los procesos la abstracción de un mapa contiguo de direcciones físicas. La memoria física se particiona en <em>marcos (page frames)</em> y la memoria lógica en <em>páginas</em> de tamaño constante (típicamente 4 KB), eliminando la <strong>fragmentación externa</strong> característica de la asignación contigua pura.</p>
            <p>Cuando un hilo solicita acceso a un puntero cuya página no está mapeada en RAM (bit de presencia en 0), la Unidad de Manejo de Memoria (MMU) intercepta la instrucción y arroja una excepción de hardware: <strong>Fallo de Página (Page Fault)</strong>. El Kernel suspende temporalmente el proceso, ejecuta I/O hacia el dispositivo de almacenamiento/swap para cargar la página, ejecuta políticas de desalojo como <strong>LRU (Least Recently Used)</strong> o <strong>Algoritmo de Reloj</strong> en marcos saturados, y restaura la ejecución.</p>
            <div class="ai-neural-box">
                <strong>Caso Práctico: Cálculo de Traducción de Dirección Lógica a Física</strong>
                <span>Para un procesador con arquitectura de 32 bits y páginas de 4 KB (2<sup>12</sup> bytes): los 12 bits menos significativos representan el desplazamiento (<em>Offset</em>) y los 20 bits más significativos el número de página virtual (<em>VPN</em>). La MMU localiza el VPN en la Tabla de Páginas para extraer el marco físico (<em>PFN</em>). Si PFN = 0x08 y Offset = 0x3BC, la dirección física definitiva resulta en <code>(0x08 << 12) | 0x3BC = 0x83BC</code>.</span>
            </div>
        </div>

        <div class="module-matrix" id="axis3">
            <h3>03 // VFS, Inodos y Subsistemas de Entrada/Salida</h3>
            <p>El Kernel desacopla las operaciones de almacenamiento mediante la capa abstracta <strong>VFS (Virtual File System)</strong>, permitiendo manipular sistemas de archivos dispares (ext4, XFS, NFS) bajo una API uniforme de llamadas al sistema (<code>open</code>, <code>read</code>, <code>write</code>). En el formato <strong>ext4</strong>, cada archivo se describe mediante un <strong>Inodo</strong>, que aloja metadatos, permisos de acceso, marcas temporales (POSIX timestamps) y apuntadores a bloques de datos físicos, desacoplando el contenido del nombre jerárquico.</p>
            <p>El flujo de datos de periféricos opera mediante <strong>interrupciones de hardware (IRQ)</strong> combinadas con controladores <strong>DMA (Direct Memory Access)</strong>, permitiendo la transferencia masiva de búferes de datos entre las tarjetas de red o controladoras NVMe directamente a la memoria RAM sin sobrecargar ciclos de reloj del procesador central.</p>
            <div class="ai-neural-box">
                <strong>Mapeo Estructural del Árbol de Directorios Unix:</strong>
                <span><code>/etc</code>: Parámetros y tablas estáticas de configuración global del sistema operativo.<br>
                <code>/var</code>: Spools, colas de impresión, bases de datos y registros dinámicos de auditoría (logs).<br>
                <code>/proc</code>: Pseudo-sistema de archivos generado en RAM que expone telemetría y estructuras del Kernel en tiempo de ejecución.<br>
                <code>/dev</code>: Interfaces lógicas de controladores de hardware abstraídas como archivos de bloque o carácter.</span>
            </div>
        </div>

        <div class="module-matrix" id="axis4">
            <h3>04 // Virtualización por Hardware vs Aislamiento por Contenedores</h3>
            <p>La virtualización completa crea entornos independientes simulando hardware físico mediante un <strong>Hipervisor Tipo 1 (Bare-Metal)</strong> como KVM, o <strong>Tipo 2 (Hosted)</strong> como VirtualBox. Cada máquina virtual carga una copia redundante del Kernel, controladores y subsistemas, demandando una partición rígida de recursos de CPU y memoria.</p>
            <p>Los <strong>Contenedores (Docker / OCI)</strong> no emulan hardware; operan sobre el mismo Kernel del host anfitrión garantizando aislamiento riguroso mediante dos pilares nativos del Kernel de Linux: <strong>Namespaces</strong> (que segregan PIDs, interfaces de red, puntos de montaje y credenciales) y <strong>CGroups (Control Groups)</strong> (que fijan cuotas máximas de consumo de CPU, RAM e I/O).</p>
            <table class="holo-table">
                <thead>
                    <tr>
                        <th>Parámetro Comparativo</th>
                        <th>Máquinas Virtuales (KVM / VirtualBox)</th>
                        <th>Contenedores (Docker / Podman)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Límite de Aislamiento</strong></td>
                        <td>Total a nivel de hardware (Kernel invitado propio)</td>
                        <td>Aislamiento lógico a nivel de Kernel (Namespaces)</td>
                    </tr>
                    <tr>
                        <td><strong>Tiempo de Inicialización</strong></td>
                        <td>Decenas de segundos a minutos</td>
                        <td>Milisegundos a escasos segundos</td>
                    </tr>
                    <tr>
                        <td><strong>Sobrecarga de RAM</strong></td>
                        <td>Alta: reserva fija de memoria para el Guest OS</td>
                        <td>Mínima: comparte dinámicamente la memoria del anfitrión</td>
                    </tr>
                    <tr>
                        <td><strong>Control de Recursos</strong></td>
                        <td>Emulación de vCPU y asignación estática</td>
                        <td>Limitación granular elástica vía CGroups</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>