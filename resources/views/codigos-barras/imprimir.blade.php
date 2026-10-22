<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas — Imprimir</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --preview-ancho: 174px; }
        body { background:#f3f4f6; font-family:system-ui,sans-serif; padding:16px; margin:0; }

        .barra {
            max-width:900px; margin:0 auto 20px; background:white;
            border-radius:12px; padding:14px 20px;
            box-shadow:0 1px 4px rgba(0,0,0,.1);
            display:flex; align-items:center; gap:12px; flex-wrap:wrap;
        }
        .grilla {
            max-width:900px; margin:0 auto;
            display:flex; flex-wrap:wrap; gap:20px;
        }
        .etiqueta-card {
            position:relative; display:flex; flex-direction:column;
            align-items:center; gap:6px;
        }
        .badge-cant {
            position:absolute; top:-9px; right:-9px;
            background:#2563eb; color:white; font-size:10px; font-weight:700;
            width:22px; height:22px; border-radius:50%;
            display:flex; align-items:center; justify-content:center; z-index:1;
        }
        .etiqueta-preview {
            width:var(--preview-ancho); background:white;
            border:1px solid #e5e7eb; border-radius:4px;
            padding:6px 8px 5px; text-align:center;
            box-shadow:0 1px 3px rgba(0,0,0,.07);
        }
        .etiqueta-preview .nom {
            font-size:10px; font-weight:700; text-transform:uppercase;
            letter-spacing:.3px; white-space:nowrap; overflow:hidden;
            text-overflow:ellipsis; color:#111; margin:0 0 1px;
        }
        .etiqueta-preview .tal { font-size:8px; color:#6b7280; margin:0 0 2px; }
        .etiqueta-preview svg  { display:block; width:100%; height:auto; margin:2px 0; }
        .etiqueta-preview .cod { font-size:7px; letter-spacing:1px; color:#6b7280; font-family:monospace; margin:1px 0 0; }

        .btn-png {
            font-size:11px; color:#2563eb; background:white;
            border:1px solid #bfdbfe; border-radius:6px;
            padding:4px 12px; cursor:pointer;
        }
        .btn-png:hover     { background:#eff6ff; }
        .btn-png:disabled  { opacity:.45; cursor:default; }

        select, input[type=number] {
            font-size:13px; border:1px solid #d1d5db;
            border-radius:8px; padding:6px 10px; background:white;
        }
        input[type=number]         { width:70px; display:none; }
        input[type=number].visible { display:inline-block; }
    </style>
</head>
<body>

<div class="barra">
    <div style="flex:1;min-width:0">
        <p style="font-size:14px;font-weight:600;color:#111;margin:0">
            {{ $etiquetas->count() }} referencia(s) —
            <span style="color:#2563eb">{{ $etiquetas->sum('cantidad') }} etiqueta(s)</span>
        </p>
        <p style="font-size:11px;color:#9ca3af;margin:3px 0 0">
            PNG a 203 DPI · Compartí a Print Master desde la galería
        </p>
    </div>

    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
        <span style="font-size:12px;color:#6b7280;white-space:nowrap">Rollo:</span>
        <select id="ancho-select" onchange="cambiarAncho(this.value)">
            <option value="40">40 mm</option>
            <option value="50">50 mm</option>
            <option value="57">57 mm</option>
            <option value="58" selected>58 mm</option>
            <option value="80">80 mm</option>
            <option value="custom">Otro…</option>
        </select>
        <input type="number" id="ancho-custom" min="20" max="210" placeholder="mm"
               oninput="anchoPersonalizado=parseInt(this.value)||0; actualizarPreview()">
    </div>

    <button id="btn-pdf" onclick="descargarPDF()"
        style="display:flex;align-items:center;gap:6px;padding:8px 18px;
               background:#2563eb;color:white;font-size:13px;font-weight:600;
               border:none;border-radius:8px;cursor:pointer;white-space:nowrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Descargar PDF
    </button>

    <button onclick="window.close()"
        style="padding:8px 14px;font-size:13px;color:#6b7280;
               border:1px solid #e5e7eb;border-radius:8px;background:white;cursor:pointer">
        Cerrar
    </button>
</div>

<div class="grilla">
    @foreach($etiquetas as $item)
        @php
            $detalle  = $item['detalle'];
            $cantidad = $item['cantidad'];
        @endphp
        <div class="etiqueta-card"
             data-nombre="{{ $detalle->producto->nombre }}"
             data-talla="{{ $detalle->talla }}"
             data-codigo="{{ $detalle->codigo_barras }}"
             data-cantidad="{{ $cantidad }}">

            @if($cantidad > 1)
            <div class="badge-cant">{{ $cantidad }}</div>
            @endif

            <div class="etiqueta-preview">
                <p class="nom" title="{{ $detalle->producto->nombre }}">
                    {{ Str::limit($detalle->producto->nombre, 22) }}
                </p>
                @if($detalle->talla)
                <p class="tal">{{ $detalle->talla }}</p>
                @endif
                <svg class="barcode-svg" data-barcode="{{ $detalle->codigo_barras }}"></svg>
                <p class="cod">{{ $detalle->codigo_barras }}</p>
            </div>

            <button class="btn-png" onclick="descargarUna(this.closest('.etiqueta-card'))">
                ↓ PNG
            </button>
        </div>
    @endforeach
</div>

<script>
const PX_POR_MM = 203 / 25.4;

let anchoMm          = parseInt(localStorage.getItem('etiqueta_ancho_mm') || '58', 10);
let anchoPersonalizado = 0;
function getAncho() { return anchoPersonalizado > 0 ? anchoPersonalizado : anchoMm; }

function cambiarAncho(valor) {
    const ci = document.getElementById('ancho-custom');
    if (valor === 'custom') {
        ci.classList.add('visible'); ci.focus();
    } else {
        ci.classList.remove('visible');
        anchoMm = parseInt(valor, 10);
        anchoPersonalizado = 0;
        localStorage.setItem('etiqueta_ancho_mm', anchoMm);
        actualizarPreview();
    }
}

function actualizarPreview() {
    const a = getAncho();
    if (a >= 20) document.documentElement.style.setProperty('--preview-ancho', (a * 3) + 'px');
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('ancho-select');
    const opt = sel.querySelector(`option[value="${anchoMm}"]`);
    if (opt) { sel.value = anchoMm; }
    else {
        sel.value = 'custom';
        const ci = document.getElementById('ancho-custom');
        ci.classList.add('visible'); ci.value = anchoMm;
        anchoPersonalizado = anchoMm;
    }
    actualizarPreview();

    document.querySelectorAll('.barcode-svg').forEach(svg => {
        const codigo = svg.dataset.barcode;
        if (!codigo || !window.JsBarcode) return;
        try {
            JsBarcode(svg, codigo, { format:'CODE128', width:1.2, height:28, displayValue:false, margin:0 });
        } catch {
            svg.outerHTML = '<p style="font-size:7px;color:red;text-align:center">Inválido</p>';
        }
    });
});

async function generarCanvas(datos) {
    const { nombre, talla, codigo } = datos;
    const anchoPx = Math.round(getAncho() * PX_POR_MM);
    const pad    = Math.round(anchoPx * 0.05);
    const usable = anchoPx - pad * 2;
    const fsNom  = Math.round(anchoPx * 0.08);
    const fsTal  = Math.round(anchoPx * 0.065);
    const fsCod  = Math.round(anchoPx * 0.05);
    const altoBc = Math.round(anchoPx * 0.30);
    const gap    = Math.round(pad * 0.5);

    const bcCanvas = document.createElement('canvas');
    let bcOk = false;
    try {
        JsBarcode(bcCanvas, codigo, {
            format:'CODE128', width:2, height:altoBc,
            displayValue:false, margin:0, background:'#ffffff',
        });
        bcOk = true;
    } catch(e) { console.warn('Barcode inválido:', codigo); }

    let totalH = pad;
    totalH += fsNom  + gap;
    if (talla)  totalH += fsTal  + gap;
    if (bcOk)   totalH += altoBc + gap;
    totalH += fsCod  + gap;
    totalH += pad;

    const canvas  = document.createElement('canvas');
    canvas.width  = anchoPx;
    canvas.height = totalH;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, anchoPx, totalH);

    let y = pad;
    ctx.textAlign = 'center'; ctx.textBaseline = 'top';

    ctx.fillStyle = '#000'; ctx.font = `bold ${fsNom}px Arial, sans-serif`;
    const nom = nombre.length > 22 ? nombre.substring(0, 22) + '…' : nombre;
    ctx.fillText(nom.toUpperCase(), anchoPx / 2, y);
    y += fsNom + gap;

    if (talla) {
        ctx.fillStyle = '#555'; ctx.font = `${fsTal}px Arial, sans-serif`;
        ctx.fillText(talla, anchoPx / 2, y);
        y += fsTal + gap;
    }

    if (bcOk) { ctx.drawImage(bcCanvas, pad, y, usable, altoBc); y += altoBc + gap; }

    ctx.fillStyle = '#444'; ctx.font = `${fsCod}px Courier New, monospace`;
    ctx.fillText(codigo, anchoPx / 2, y);

    return canvas;
}

function dispararDescarga(canvas, filename) {
    canvas.toBlob(blob => {
        const url = URL.createObjectURL(blob);
        const a   = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }, 'image/png');
}

async function descargarUna(card) {
    const btn = card.querySelector('.btn-png');
    btn.textContent = '…'; btn.disabled = true;
    try {
        const canvas = await generarCanvas({
            nombre: card.dataset.nombre, talla: card.dataset.talla,
            codigo: card.dataset.codigo,
        });
        dispararDescarga(canvas, `etiqueta_${card.dataset.codigo}.png`);
    } catch(e) { alert('Error: ' + e.message); }
    finally { btn.textContent = '↓ PNG'; btn.disabled = false; }
}

// Orientación correcta para jsPDF según dimensiones (evita que voltee la página)
function orient(w, h) { return w > h ? 'landscape' : 'portrait'; }

async function descargarPDF() {
    const ancho = getAncho();
    if (!ancho || ancho < 20) { alert('Selecciona el ancho del rollo.'); return; }

    const btn   = document.getElementById('btn-pdf');
    const cards = document.querySelectorAll('.etiqueta-card');
    btn.disabled = true;

    try {
        let doc = null;

        for (let idx = 0; idx < cards.length; idx++) {
            btn.textContent = `${idx + 1}/${cards.length}…`;
            const card     = cards[idx];
            const cantidad = Math.max(1, parseInt(card.dataset.cantidad, 10) || 1);
            const canvas   = await generarCanvas({
                nombre: card.dataset.nombre, talla: card.dataset.talla,
                codigo: card.dataset.codigo,
            });
            const altoMm = canvas.height / PX_POR_MM;
            const imagen = canvas.toDataURL('image/png');
            // Alias: jsPDF guarda la imagen una sola vez aunque se repita en varias páginas
            const alias  = 'etq_' + idx;

            // Una página por cada unidad de la cantidad
            for (let n = 0; n < cantidad; n++) {
                if (!doc) {
                    doc = new window.jsPDF({
                        unit:        'mm',
                        format:      [ancho, altoMm],
                        orientation: orient(ancho, altoMm),
                    });
                } else {
                    doc.addPage([ancho, altoMm], orient(ancho, altoMm));
                }
                doc.addImage(imagen, 'PNG', 0, 0, ancho, altoMm, alias);
            }
        }

        doc.save('etiquetas.pdf');
    } catch(e) {
        console.error(e); alert('Error: ' + e.message);
    } finally {
        btn.textContent = 'Descargar PDF'; btn.disabled = false;
    }
}
</script>
</body>
</html>
