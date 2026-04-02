// =========================================
// --- LÓGICA PARA EL PÓSTER DE LA JORNADA ---
// =========================================

// Variables globales para controlar la edición y el arrastre
let currentRoundForPoster = '';
let activeFlyerElement = null; 
let dragElement = null;        
let startX = 0, startY = 0;    
let initialLeft = 0, initialTop = 0; 

// --- INICIALIZACIÓN ---
document.addEventListener("DOMContentLoaded", () => {
    // 1. Cargar fondos disponibles
    loadFlyerBackgrounds(); 
    
    // 2. Deseleccionar si se hace clic fuera del lienzo o herramientas
    document.addEventListener('click', (e) => {
        const modalFlyer = document.getElementById('modalFlyer');
        if(modalFlyer && modalFlyer.classList.contains('show')) {
            if(!e.target.closest('#flyer-canvas') && !e.target.closest('#flyer-design-tools') && !e.target.closest('.modal-header') && !e.target.closest('.bi-trash')) {
                deselectFlyerElement();
            }
        }
    });

    // =========================================
    // --- EVENTOS DEL EDITOR VISUAL (NUEVO) ---
    // =========================================
    const flyerCanvas = document.getElementById('flyer-canvas');
    
    // Auto-escalado del lienzo
    window.addEventListener('resize', scaleCanvasToFit);
    const modalFlyerEl = document.getElementById('modalFlyer');
    if (modalFlyerEl) {
        modalFlyerEl.addEventListener('shown.bs.modal', scaleCanvasToFit);
    }

    if(flyerCanvas) {
        // A. DOBLE CLIC para editar texto
        flyerCanvas.addEventListener('dblclick', function(e) {
            const editableText = e.target.closest('.editable');
            if (editableText) {
                editableText.contentEditable = "true";
                editableText.classList.add('text-typing');
                editableText.focus();
                
                // Mover cursor al final del texto
                const range = document.createRange();
                const sel = window.getSelection();
                range.selectNodeContents(editableText);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        });

        // B. Salir del modo edición al perder el foco
        flyerCanvas.addEventListener('focusout', function(e) {
            if (e.target.classList.contains('editable')) {
                e.target.contentEditable = "false";
                e.target.classList.remove('text-typing');
            }
        });

        // --- FUNCIÓN AUXILIAR PARA OBTENER COORDENADAS (RATÓN O TÁCTIL) ---
        function getPointerPos(e) {
            return {
                x: e.touches ? e.touches[0].clientX : e.clientX,
                y: e.touches ? e.touches[0].clientY : e.clientY
            };
        }

        // C. INICIO DEL ARRASTRE (Mousedown / Touchstart)
        function handleDragStart(e) {
            const target = e.target.closest('.draggable');
            
            // Si el elemento está en modo escritura, no arrastrar
            if (target && target.classList.contains('text-typing')) return;

            if (target) {
                selectFlyerElement(target);
                initDrag(target, e);
            } else {
                deselectFlyerElement();
            }
        }

        // Escuchamos ambos eventos (Ratón y Táctil)
        flyerCanvas.addEventListener('mousedown', handleDragStart);
        flyerCanvas.addEventListener('touchstart', handleDragStart, { passive: true });
    }

    // D. Funciones de Arrastre con compensación de Zoom
    function initDrag(element, e) {
        dragElement = element;
        
        // Obtener zoom actual
        const transformStr = flyerCanvas.style.transform || '';
        const match = transformStr.match(/scale\(([^)]+)\)/);
        const zoomFactor = match ? parseFloat(match[1]) : 1;

        // Convertir translate a pixeles absolutos para evitar saltos
        if(dragElement.style.transform.includes('translate')) {
            const rect = dragElement.getBoundingClientRect();
            const parentRect = flyerCanvas.getBoundingClientRect();
            dragElement.style.transform = 'none'; 
            
            dragElement.style.left = ((rect.left - parentRect.left) / zoomFactor) + 'px';
            dragElement.style.top = ((rect.top - parentRect.top) / zoomFactor) + 'px';
        }
        
        const pos = getPointerPos(e);
        startX = pos.x;
        startY = pos.y;
        initialLeft = parseFloat(dragElement.style.left) || 0;
        initialTop = parseFloat(dragElement.style.top) || 0;
    }

    // MOVIMIENTO DEL ARRASTRE (Mousemove / Touchmove)
    function handleDragMove(e) {
        if (dragElement && !dragElement.classList.contains('text-typing')) {
            // Evitar que la pantalla haga scroll mientras arrastramos un elemento
            if (e.type === 'touchmove') e.preventDefault(); 

            const transformStr = flyerCanvas.style.transform || '';
            const match = transformStr.match(/scale\(([^)]+)\)/);
            const zoomFactor = match ? parseFloat(match[1]) : 1;

            const pos = getPointerPos(e);
            const dx = (pos.x - startX) / zoomFactor;
            const dy = (pos.y - startY) / zoomFactor;
            
            dragElement.style.left = (initialLeft + dx) + 'px';
            dragElement.style.top = (initialTop + dy) + 'px';
        }
    }

    // FIN DEL ARRASTRE (Mouseup / Touchend)
    function handleDragEnd() {
        if (dragElement) dragElement = null; 
    }

    // Escuchamos los movimientos en todo el documento para que no se pierda el rastro si el dedo sale del lienzo
    document.addEventListener('mousemove', handleDragMove);
    document.addEventListener('touchmove', handleDragMove, { passive: false }); // passive: false es necesario para usar preventDefault()
    document.addEventListener('mouseup', handleDragEnd);
    document.addEventListener('touchend', handleDragEnd);

    document.addEventListener('mousemove', function(e) {
        if (dragElement && !dragElement.classList.contains('text-typing')) {
            const transformStr = flyerCanvas.style.transform || '';
            const match = transformStr.match(/scale\(([^)]+)\)/);
            const zoomFactor = match ? parseFloat(match[1]) : 1;

            const dx = (e.clientX - startX) / zoomFactor;
            const dy = (e.clientY - startY) / zoomFactor;
            
            dragElement.style.left = (initialLeft + dx) + 'px';
            dragElement.style.top = (initialTop + dy) + 'px';
        }
    });

    document.addEventListener('mouseup', function() {
        if (dragElement) dragElement = null; 
    });

    // =========================================
    // --- CONEXIÓN DE BARRA DE HERRAMIENTAS ---
    // =========================================
    
    document.getElementById('tool-color')?.addEventListener('input', (e) => {
        if(activeFlyerElement && activeFlyerElement.classList.contains('editable')) {
            activeFlyerElement.style.color = e.target.value;
        }
    });

    document.getElementById('tool-bgcolor')?.addEventListener('input', (e) => {
        if(activeFlyerElement && activeFlyerElement.classList.contains('editable')) {
            activeFlyerElement.style.backgroundColor = e.target.value;
        }
    });

    document.getElementById('tool-font')?.addEventListener('change', (e) => {
        if(activeFlyerElement && activeFlyerElement.classList.contains('editable')) {
            activeFlyerElement.style.fontFamily = e.target.value;
        }
    });

    document.getElementById('tool-size')?.addEventListener('input', (e) => {
        if(activeFlyerElement) {
            if (activeFlyerElement.classList.contains('flyer-logo-img')) {
                // Al editar a mano, forzamos que el width Y el height cambien al mismo tiempo
                const newSize = (e.target.value * 2) + 'px';
                activeFlyerElement.style.width = newSize; 
                activeFlyerElement.style.height = newSize; 
            } else if (activeFlyerElement.classList.contains('editable')) {
                activeFlyerElement.style.fontSize = e.target.value + 'px';
            }
        }
    });
});


// =========================================
// --- MANEJO DE VISTA Y ESCALADO ---
// =========================================

function scaleCanvasToFit() {
    const wrapper = document.getElementById('flyer-container-wrapper');
    const flyerCanvas = document.getElementById('flyer-canvas');
    if(wrapper && flyerCanvas) {
        // Dejar 40px de margen
        const availableHeight = wrapper.clientHeight - 40; 
        const scale = Math.min(1, availableHeight / 900); // 900px es la altura real del diseño
        flyerCanvas.style.transform = `scale(${scale})`;
    }
}


// =========================================
// --- GENERACIÓN E INYECCIÓN DEL DISEÑO ---
// =========================================

function previewFlyer(roundName) {
    currentRoundForPoster = roundName;
    
    const matches = typeof matchesByRound !== 'undefined' ? matchesByRound[roundName] : [];
    const contentArea = document.getElementById('flyer-content-area');
    
    contentArea.innerHTML = ''; 

    if(!matches || matches.length === 0) {
        addTextToFlyer("No hay partidos programados", 50, 50, 'flyer-free-text', 2.5);
    } else {
        // --- ALGORITMO DINÁMICO DE ESPACIADO Y ESCALADO ---
        const count = matches.length;
        let startY = 15;  
        let stepY = 26;   
        let scale = 1;    

        if (count === 1) { startY = 50; stepY = 0; scale = 1.3; }
        else if (count === 2) { startY = 30; stepY = 40; scale = 1.1; }
        else if (count === 3) { startY = 20; stepY = 30; scale = 1.0; }
        else if (count === 4) { startY = 15; stepY = 23; scale = 0.9; }
        else if (count === 5) { startY = 12; stepY = 19; scale = 0.75; }
        else if (count >= 6) { startY = 10; stepY = 85 / count; scale = 0.60; } 

        let offsetY = startY; 
        
        matches.forEach(m => {
            if(m.status === 'CANCELLED') return; 

            let timeStr = 'POR DEFINIR';
            let dateStr = 'Fecha por definir';

            if(m.scheduled_datetime) {
                const dt = new Date(m.scheduled_datetime);
                timeStr = dt.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) + ' HRS';
                dateStr = dt.toLocaleDateString('es-ES', { weekday: 'long', day: '2-digit', month: 'short' });
            }

            const teamA = m.team_a.substring(0, 15); 
            const teamB = m.team_b.substring(0, 15);

            const logoA = m.logo_a ? `../${m.logo_a}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_a)}&background=f1f5f9&color=64748b&size=128`;
            const logoB = m.logo_b ? `../${m.logo_b}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(m.team_b)}&background=f1f5f9&color=64748b&size=128`;

            const logoY = offsetY - (8 * scale);
            const vsY = offsetY - (3 * scale);
            const timeY = offsetY + (6 * scale);
            const dateY = offsetY + (11 * scale);

            // Inyectar logos
            addImageToFlyer(logoA, 25, logoY, 80 * scale); 
            addImageToFlyer(logoB, 75, logoY, 80 * scale);

            // Inyectar Equipo A (Fondo Transparente)
            const txtTeamA = addTextToFlyer(teamA, 25, offsetY, 'flyer-style-teams', 1.5 * scale);
            txtTeamA.style.backgroundColor = 'transparent';
            txtTeamA.style.color = '#ffffff';

            // Inyectar VS
            addTextToFlyer(" VS ", 50, vsY, 'flyer-free-text', 2.5 * scale).style.color = '#ffffff'; 

            // Inyectar Equipo B (Fondo Transparente)
            const txtTeamB = addTextToFlyer(teamB, 75, offsetY, 'flyer-style-teams', 1.5 * scale);
            txtTeamB.style.backgroundColor = 'transparent';
            txtTeamB.style.color = '#ffffff';
            
            // Inyectar Hora (Fondo Transparente)
            const txtTime = addTextToFlyer(`A LAS ${timeStr}`, 50, timeY, 'flyer-style-time', 1.2 * scale);
            txtTime.style.backgroundColor = 'transparent';
            txtTime.style.color = '#ffffff';

            // Inyectar Fecha
            addTextToFlyer(dateStr.toUpperCase(), 50, dateY, 'flyer-style-date', 1.2 * scale).style.color = '#cbd5e1';
            
            offsetY += stepY; 
        });
    }

    new bootstrap.Modal(document.getElementById('modalFlyer')).show();
    setTimeout(scaleCanvasToFit, 150); 
}

function addTextToFlyer(defaultText = "NUEVO TEXTO", posX = 50, posY = 15, customClass = 'flyer-free-text', fontSizeRem = 2.5) {
    const contentArea = document.getElementById('flyer-content-area');
    const newText = document.createElement('div');
    
    newText.className = `draggable editable ${customClass}`;
    newText.contentEditable = "false";
    newText.spellcheck = false;
    newText.innerText = defaultText;
    
    newText.style.position = 'absolute';
    newText.style.left = `${posX}%`;
    newText.style.top = `${posY}%`;
    newText.style.transform = 'translate(-50%, -50%)';
    newText.style.zIndex = '10';
    
    // Inyectamos el tamaño de fuente dinámico
    newText.style.fontSize = `${fontSizeRem}rem`;
    
    contentArea.appendChild(newText);
    
    if(defaultText === "NUEVO TEXTO") selectFlyerElement(newText); 
    return newText;
}

function addImageToFlyer(imgSrc, posX = 50, posY = 15, size = 80) {
    const contentArea = document.getElementById('flyer-content-area');
    const newImg = document.createElement('img');
    
    newImg.className = 'draggable flyer-logo-img shadow-sm';
    newImg.src = imgSrc;
    newImg.crossOrigin = "anonymous"; 
    
    newImg.style.position = 'absolute';
    newImg.style.left = `${posX}%`;
    newImg.style.top = `${posY}%`;
    newImg.style.transform = 'translate(-50%, -50%)';
    newImg.style.zIndex = '10';
    
    newImg.style.width = `${size}px`;
    newImg.style.height = `${size}px`; 
    newImg.style.objectFit = 'contain';
    newImg.style.borderRadius = '8px';
    newImg.style.backgroundColor = 'rgba(255,255,255,0.9)'; 
    newImg.style.padding = '5px';
    
    newImg.onerror = function() { this.src = 'https://placehold.co/80x80?text=TM'; };

    contentArea.appendChild(newImg);
    return newImg;
}

// =========================================
// --- MANEJO DEL ESTADO DE SELECCIÓN ---
// =========================================

function rgbToHex(color) {
    if (!color) return '#ffffff';
    if (color.startsWith('#')) return color;
    const rgb = color.match(/\d+/g);
    if (!rgb) return '#ffffff';
    return '#' + rgb.slice(0, 3).map(x => {
        const hex = parseInt(x).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    }).join('');
}

function selectFlyerElement(el) {
    if(activeFlyerElement) activeFlyerElement.classList.remove('editing-active');
    
    activeFlyerElement = el;
    activeFlyerElement.classList.add('editing-active');
    
    const tools = document.getElementById('flyer-design-tools');
    if(tools) {
        tools.classList.remove('d-none');
        tools.classList.add('d-flex');
    }
    
    const computedStyle = window.getComputedStyle(activeFlyerElement);
    
    if(activeFlyerElement.classList.contains('editable')) {
        document.getElementById('tool-color').disabled = false;
        document.getElementById('tool-bgcolor').disabled = false;
        document.getElementById('tool-font').disabled = false;
        
        document.getElementById('tool-color').value = rgbToHex(computedStyle.color);
        document.getElementById('tool-bgcolor').value = computedStyle.backgroundColor === 'rgba(0, 0, 0, 0)' ? '#ffffff' : rgbToHex(computedStyle.backgroundColor);
        
        document.getElementById('tool-size').value = parseInt(computedStyle.fontSize) || 16;
        
        const fontVal = computedStyle.fontFamily.replace(/"/g, "'");
        const fontSelect = document.getElementById('tool-font');
        if(fontSelect) {
            for(let i=0; i<fontSelect.options.length; i++) {
                if(fontSelect.options[i].value.includes(fontVal.split(',')[0])) {
                    fontSelect.selectedIndex = i;
                    break;
                }
            }
        }
    } 
    else if(activeFlyerElement.classList.contains('flyer-logo-img')) {
        document.getElementById('tool-color').disabled = true;
        document.getElementById('tool-bgcolor').disabled = true;
        document.getElementById('tool-font').disabled = true;
        
        document.getElementById('tool-size').value = (parseInt(computedStyle.width) / 2) || 40; 
    }
}

function deselectFlyerElement() {
    if(activeFlyerElement) {
        activeFlyerElement.classList.remove('editing-active');
        activeFlyerElement.classList.remove('text-typing');
        activeFlyerElement.contentEditable = "false";
    }
    activeFlyerElement = null;
    
    const tools = document.getElementById('flyer-design-tools');
    if(tools) {
        tools.classList.add('d-none');
        tools.classList.remove('d-flex');
    }
}

function deleteActiveFlyerElement() {
    if(activeFlyerElement) {
        activeFlyerElement.remove(); 
        deselectFlyerElement(); 
    }
}


// =========================================
// --- DESCARGAR Y GENERAR PÓSTER ---
// =========================================

function downloadFlyer() {
    deselectFlyerElement(); // Quitar contornos antes de la captura
    
    const btn = document.getElementById('btnDownloadFlyer');
    const originalHTML = '<i class="bi bi-download"></i>';
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    btn.disabled = true;

    const flyerElement = document.getElementById('flyer-canvas');
    const flyerWrapper = document.getElementById('flyer-container-wrapper');
    
    // Detectar si el dispositivo es móvil
    const isMobile = window.innerWidth <= 768;
    
    // Guardamos el estado original para restaurarlo después
    const originalTransform = flyerElement.style.transform;
    const originalPosition = flyerElement.style.position;
    const originalTop = flyerElement.style.top;
    const originalLeft = flyerElement.style.left;
    const originalZIndex = flyerElement.style.zIndex;
    const originalWrapperOverflow = flyerWrapper.style.overflow;

    // --- TRUCO CRÍTICO PARA MÓVILES ---
    // Sacamos el lienzo del flujo normal, lo ponemos en absoluto
    // y restauramos su escala original a 1
    flyerElement.style.transform = 'scale(1)';
    flyerElement.style.position = 'absolute';
    flyerElement.style.top = '0';
    flyerElement.style.left = '0';
    flyerElement.style.zIndex = '-9999';
    flyerWrapper.style.overflow = 'visible'; 

    html2canvas(flyerElement, {
        scale: isMobile ? 1.5 : 2, 
        useCORS: true, 
        backgroundColor: '#0f172a',
        logging: false,
        imageTimeout: 15000, 
        // Forzamos explícitamente el ancho y alto del contenedor original
        width: 600, 
        height: 900,
        // ESTO ES LO NUEVO: Obligamos al renderizador a usar un tamaño de ventana virtual
        // para que las fuentes relativas (rem) y los porcentajes (%) se calculen igual que en PC
        windowWidth: 1200,
        windowHeight: 900,
        x: 0,
        y: 0,
        scrollX: 0,
        scrollY: 0
    }).then(canvas => {
        // Restauramos inmediatamente la vista a como estaba
        flyerElement.style.transform = originalTransform;
        flyerElement.style.position = originalPosition;
        flyerElement.style.top = originalTop;
        flyerElement.style.left = originalLeft;
        flyerElement.style.zIndex = originalZIndex;
        flyerWrapper.style.overflow = originalWrapperOverflow;

        const link = document.createElement('a');
        const safeRoundName = currentRoundForPoster.replace(/\s+/g, '_');
        
        if (isMobile) {
            link.download = `Rol_Juegos_${safeRoundName}.jpg`;
            // JPEG al 95% para ahorrar memoria sin perder calidad visual
            link.href = canvas.toDataURL('image/jpeg', 0.95); 
        } else {
            link.download = `Rol_Juegos_${safeRoundName}.png`;
            link.href = canvas.toDataURL('image/png'); 
        }
        
        link.click();

        btn.innerHTML = originalHTML;
        btn.disabled = false;
        
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Póster descargado', showConfirmButton: false, timer: 3000 });
    }).catch(err => {
        // Restaurar en caso de error
        flyerElement.style.transform = originalTransform;
        flyerElement.style.position = originalPosition;
        flyerElement.style.top = originalTop;
        flyerElement.style.left = originalLeft;
        flyerElement.style.zIndex = originalZIndex;
        flyerWrapper.style.overflow = originalWrapperOverflow;

        console.error("Error al generar imagen:", err);
        Swal.fire('Error', 'Memoria insuficiente o error al procesar. Intenta cerrar otras apps.', 'error');
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    });
}
// =========================================
// --- FONDOS DESDE EL SERVIDOR PHP ---
// =========================================

function changeFlyerBackground(val) {
    const img = document.getElementById('flyer-bg-img'); 
    if(val) { 
        img.src = val; 
        img.style.display = 'block'; 
    } else { 
        img.removeAttribute('src'); 
        img.style.display = 'none'; 
    }
}

async function loadFlyerBackgrounds(selectUrl = null) {
    try {
        const res = await fetch(`${API_URL}?action=get_files&folder=imagenes/imagenesRol`);
        const json = await res.json();
        
        const select = document.getElementById('flyerBgSelect');
        const imgElement = document.getElementById('flyer-bg-img');
        
        if (!select || !imgElement) return;

        select.innerHTML = '<option value="">-- Sin fondo (Color sólido) --</option>';
        
        if(json.status === 'success' && json.data && Array.isArray(json.data) && json.data.length > 0) {
            json.data.forEach(img => {
                select.innerHTML += `<option value="${img.url}">${img.name}</option>`;
            });
        } else {
            select.innerHTML += '<option value="" disabled>No hay fondos subidos aún</option>';
        }
        
        if(selectUrl) {
            select.value = selectUrl;
            imgElement.src = selectUrl;
            imgElement.style.display = 'block';
        } else {
            imgElement.removeAttribute('src');
            imgElement.style.display = 'none';
        }
    } catch(e) { console.error("Error cargando fondos", e); }
}

async function uploadFlyerBackground(input) {
    if(!input.files || input.files.length === 0) return;
    
    const formData = new FormData();
    formData.append('file', input.files[0]);
    formData.append('action', 'upload_rol_image'); 

    try {
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const json = await res.json();
        if(json.status === 'success') {
            loadFlyerBackgrounds(json.data.url);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Fondo agregado', showConfirmButton: false, timer: 2000 });
        } else {
            Swal.fire('Error', json.message, 'error');
            loadFlyerBackgrounds();
        }
    } catch(e) {
        Swal.fire('Error', 'Fallo al subir la imagen', 'error');
        loadFlyerBackgrounds();
    }
}