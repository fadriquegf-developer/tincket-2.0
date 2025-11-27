/* ------------------------------------------------------------------
 *  Vue 3 · Plano de butacas · selección y edición masiva
 * ----------------------------------------------------------------*/
(function () {
    const STATUS_COLORS = {
        null: "#a3d165", // libre
        1: "#ffa500", // disponible
        2: "#e53935", // vendida
        3: "#800080", // reservada
        4: "#7b68ee", // pack
        5: "#ffffff", // oculta
        6: "#696969", // bloqueada
        7: "#b9b9b9", // covid19
        8: "#0368ae", // discapacidad
        // …añade los que necesites
    };

    /*  color y grosor del borde cuando la butaca está libre  */
    const AVAILABLE_STROKE = "#008102";
    const STROKE_WIDTH = 2;

    /* ==============================================================
     *  Componente Vue
     * ==============================================================*/
    const SvgLayout = {
        template: `<div ref="canvas" class="svg-layout"></div>`,

        /* props que llegan del blade */
        props: {
            svgUrl: String,
            slots: Array,
            zones: Array,
            configId: Number,
        },

        data() {
            return {
                /* selección */
                selected: new Set(),
                lastIndex: null,
                /* drag-select */
                isDragging: false,
                dragRectEl: null,
                dragStartX: 0,
                dragStartY: 0,
                /* nodos y datos */
                nodesArray: [],
                nodesById: {},
                slotById: {},
                statusColor: STATUS_COLORS,
                zoneColorMap: {}, // mapa zone_id → color
                changed: {}, // id → payload para backend
                /* popover */
                popoverEl: null,
                currentPopoverNode: null,
                popoverTimeout: null,
                hasSingleZone: false,
            };
        },

        methods: {
            valorComun(arr, prop) {
                if (!arr.length) return null;
                const first = arr[0][prop];
                return arr.every((o) => o[prop] === first) ? first : null;
            },
            /* selección básica */
            toggleNode(node, sel) {
                const id = +node.dataset.slotId;
                if (sel) {
                    this.selected.add(id);
                    node.classList.add("selected");
                } else {
                    this.selected.delete(id);
                    node.classList.remove("selected");
                }
            },
            clearSelection() {
                this.nodesArray.forEach((n) => n.classList.remove("selected"));
                this.selected.clear();
            },

            /* escribir ids o cambios en el <input hidden> */
            writeHidden(payload = [...this.selected]) {
                document.getElementById("slot_labels_input").value =
                    JSON.stringify(payload);
            },

            /* drag-select */
            startDrag(e) {
                // Ignorar si no es click izquierdo o si el target es una butaca
                if (e.button !== 0 || e.target.classList.contains("slot"))
                    return;

                // Prevenir el comportamiento por defecto
                e.preventDefault();

                // Ocultar popover al iniciar drag
                this.hidePopover();

                this.isDragging = true;
                const r = this.$refs.canvas.getBoundingClientRect();
                this.dragStartX = e.clientX - r.left;
                this.dragStartY = e.clientY - r.top;

                // Crear el rectángulo de selección
                this.dragRectEl = document.createElement("div");
                this.dragRectEl.className = "drag-select-rect";
                Object.assign(this.dragRectEl.style, {
                    left: this.dragStartX + "px",
                    top: this.dragStartY + "px",
                    width: "0px",
                    height: "0px",
                });
                this.$refs.canvas.appendChild(this.dragRectEl);

                // Añadir listeners globales
                window.addEventListener("mousemove", this.onDrag);
                window.addEventListener("mouseup", this.stopDrag);
                // Añadir listener para cuando el mouse sale de la ventana
                document.addEventListener("mouseleave", this.cancelDrag);
            },

            onDrag(e) {
                if (!this.isDragging || !this.dragRectEl) return;

                const r = this.$refs.canvas.getBoundingClientRect();
                const curX = e.clientX - r.left;
                const curY = e.clientY - r.top;
                const x = Math.min(curX, this.dragStartX);
                const y = Math.min(curY, this.dragStartY);
                const w = Math.abs(curX - this.dragStartX);
                const h = Math.abs(curY - this.dragStartY);

                Object.assign(this.dragRectEl.style, {
                    left: x + "px",
                    top: y + "px",
                    width: w + "px",
                    height: h + "px",
                });
            },

            stopDrag(e) {
                if (!this.isDragging) return;

                // Si hay un rectángulo y tiene tamaño significativo, procesar la selección
                if (this.dragRectEl) {
                    const rect = this.dragRectEl.getBoundingClientRect();
                    const hasSize = rect.width > 5 && rect.height > 5; // Solo si el rectángulo tiene un tamaño mínimo

                    if (hasSize) {
                        const add = e.ctrlKey || e.metaKey;
                        if (!add) this.clearSelection();

                        this.nodesArray.forEach((node) => {
                            const b = node.getBoundingClientRect();
                            const centerX = b.left + b.width / 2;
                            const centerY = b.top + b.height / 2;
                            // Verificar si el centro de la butaca está dentro del rectángulo
                            const inside =
                                centerX >= rect.left &&
                                centerX <= rect.right &&
                                centerY >= rect.top &&
                                centerY <= rect.bottom;
                            if (inside) this.toggleNode(node, true);
                        });

                        this.writeHidden();
                    }
                }

                // Limpiar siempre
                this.cleanupDrag();
            },

            cancelDrag() {
                // Cancelar el drag si el mouse sale del documento
                if (this.isDragging) {
                    this.cleanupDrag();
                }
            },

            cleanupDrag() {
                this.isDragging = false;

                // Remover listeners
                window.removeEventListener("mousemove", this.onDrag);
                window.removeEventListener("mouseup", this.stopDrag);
                document.removeEventListener("mouseleave", this.cancelDrag);

                // Remover el rectángulo del DOM si existe
                if (this.dragRectEl && this.dragRectEl.parentNode) {
                    this.dragRectEl.remove();
                }
                this.dragRectEl = null;
            },

            /* Métodos para el popover */
            createPopover() {
                if (!this.popoverEl) {
                    this.popoverEl = document.createElement("div");
                    this.popoverEl.className = "slot-popover";
                    this.popoverEl.style.cssText = `
                        position: fixed;
                        background: rgba(0, 0, 0, 0.9);
                        color: white;
                        padding: 10px 15px;
                        border-radius: 6px;
                        font-size: 13px;
                        line-height: 1.5;
                        pointer-events: none;
                        z-index: 10000;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        max-width: 250px;
                        word-wrap: break-word;
                        display: none;
                    `;
                    document.body.appendChild(this.popoverEl);
                }
            },

            showPopover(node, e) {
                if (this.isDragging) return;

                const sid = +node.dataset.slotId;
                const slot = this.slotById[sid];

                if (!slot) return;

                this.createPopover();

                // Construir contenido del popover
                let content = "";

                // Obtener el color del estado
                const stId = slot.status_id;
                const statusColor =
                    stId === null
                        ? AVAILABLE_STROKE
                        : this.statusColor[stId] || "#666";

                if (slot.name) {
                    content += `<div style="font-weight: 600; margin-bottom: 5px;">
                        <span style="color: ${statusColor};">●</span> ${this.escapeHtml(
                        slot.name
                    )}
                    </div>`;
                }

                if (slot.comment) {
                    content += `<div style="color: #e0e0e0; font-size: 12px;">
                        ${this.escapeHtml(slot.comment)}
                    </div>`;
                }

                // Si no hay ni nombre ni comentario, no mostrar popover
                if (!content) return;

                this.popoverEl.innerHTML = content;
                this.currentPopoverNode = node;

                // Posicionar el popover
                this.positionPopover(e);

                // Mostrar con delay
                clearTimeout(this.popoverTimeout);
                this.popoverTimeout = setTimeout(() => {
                    if (this.popoverEl && this.currentPopoverNode === node) {
                        this.popoverEl.style.display = "block";
                    }
                }, 300);
            },

            positionPopover(e) {
                if (!this.popoverEl) return;

                const offset = 15;
                let left = e.clientX + offset;
                let top = e.clientY + offset;

                // Obtener dimensiones del popover sin ocultarlo
                const rect = this.popoverEl.getBoundingClientRect();

                // Ajustar si se sale de la ventana por la derecha
                if (left + rect.width > window.innerWidth) {
                    left = e.clientX - rect.width - offset;
                }

                // Ajustar si se sale de la ventana por abajo
                if (top + rect.height > window.innerHeight) {
                    top = e.clientY - rect.height - offset;
                }

                this.popoverEl.style.left = left + "px";
                this.popoverEl.style.top = top + "px";
            },

            hidePopover() {
                clearTimeout(this.popoverTimeout);
                if (this.popoverEl) {
                    this.popoverEl.style.display = "none";
                }
                this.currentPopoverNode = null;
            },

            escapeHtml(text) {
                const div = document.createElement("div");
                div.textContent = text;
                return div.innerHTML;
            },

            /* abrir modal de edición */
            openEditModal() {
                if (this.selected.size === 0) {
                    const text = "Selecciona una butaca.";

                    if (window.swal) {
                        swal({
                            title: text,
                            icon: "warning",
                            buttons: {
                                confirm: {
                                    text: "OK",
                                    visible: true,
                                    className: "bg-primary", // estilo Backpack
                                    closeModal: true,
                                },
                            },
                        });
                    } else {
                        alert(text);
                    }
                    return;
                }
                // ← ZONAS: vaciamos y poblamos el select cada vez que abrimos el modal
                const zoneSelect = document.getElementById("set-slot-zone");
                zoneSelect.innerHTML =
                    '<option value="">-- Selecciona zona --</option>';
                this.zones.forEach((z) => {
                    const opt = document.createElement("option");
                    opt.value = z.id;
                    opt.textContent = z.name;
                    zoneSelect.appendChild(opt);
                });

                const slotsSel = [...this.selected].map(
                    (id) => this.slotById[id]
                );

                // 2) Calculamos los valores comunes
                const zoneCommon = this.valorComun(slotsSel, "zone_id");
                const xCommon = this.valorComun(slotsSel, "x");
                const yCommon = this.valorComun(slotsSel, "y");
                const statusCommon = this.valorComun(slotsSel, "status_id");
                const commentCommon = this.valorComun(slotsSel, "comment");

                // 3) Rellenamos el formulario
                $("#set-slot-id").val(
                    slotsSel.length === 1 ? slotsSel[0].id : "Múltiples"
                );

                // ── Estado
                $("#set-slot-status").val(
                    statusCommon !== null ? statusCommon : "null"
                );

                // ── Comentario (mostrar si es común, vacío si es múltiple/diferente)
                $('#setSlotProperties input[name="comment"]').val(
                    commentCommon !== null ? commentCommon : ""
                );

                // ── Nombre solo editable si es uno solo
                $("#set-slot-name")
                    .prop("disabled", slotsSel.length !== 1)
                    .val(slotsSel.length === 1 ? slotsSel[0].name ?? "" : "");

                // ── Coordenadas
                $("#set-slot-x").val(xCommon !== null ? xCommon : "");
                $("#set-slot-y").val(yCommon !== null ? yCommon : "");

                // ── Zona
                $("#set-slot-zone").val(zoneCommon !== null ? zoneCommon : "");

                // 4) Mostrar modal
                $("#setSlotProperties").modal("show");
            },

            /* aplicar cambios del modal */
            applyModalChanges() {
                const statusVal = $("#set-slot-status").val();
                const newStatus = statusVal === "null" ? null : +statusVal;
                const newComment = $(
                    '#setSlotProperties input[name="comment"]'
                ).val();
                const newName = $("#set-slot-name").prop("disabled")
                    ? null
                    : $("#set-slot-name").val().trim();
                const newX = parseFloat($("#set-slot-x").val());
                const newY = parseFloat($("#set-slot-y").val());
                const newZone = parseInt($("#set-slot-zone").val(), 10) || null;

                this.selected.forEach((id) => {
                    const node = this.nodesById[id];
                    const slot = this.slotById[id];

                    if (newName) slot.name = newName;
                    slot.status_id = newStatus;
                    slot.comment = newComment;
                    slot.x = isNaN(newX) ? null : newX;
                    slot.y = isNaN(newY) ? null : newY;
                    slot.zone_id = newZone;

                    const newStatusColor =
                        newStatus === null
                            ? AVAILABLE_STROKE
                            : this.statusColor[newStatus] ?? "#666";
                    node.style.fill = newStatusColor;

                    // Actualizar stroke según número de zonas
                    if (this.hasSingleZone) {
                        node.style.stroke = newStatusColor;
                        node.style.strokeWidth = STROKE_WIDTH;
                    } else {
                        const zoneColor =
                            this.zoneColorMap[slot.zone_id] || "#cccccc";
                        node.style.stroke = zoneColor;
                        node.style.strokeWidth = 2;
                    }

                    this.changed[id] = {
                        id,
                        name: slot.name ?? null,
                        comment: slot.comment ?? null,
                        status_id: slot.status_id ?? null,
                        x: slot.x,
                        y: slot.y,
                        zone_id: newZone,
                    };
                });

                document.getElementById("slot_labels_input").value =
                    JSON.stringify(Object.values(this.changed));
                $("#setSlotProperties").modal("hide");
            },
        },

        beforeUnmount() {
            // Limpiar si el componente se desmonta mientras arrastra
            this.cleanupDrag();
            // Limpiar popover al destruir el componente
            if (this.popoverEl) {
                this.popoverEl.remove();
                this.popoverEl = null;
            }
            clearTimeout(this.popoverTimeout);
        },

        mounted() {
            const canvas = this.$refs.canvas;

            // Evento para iniciar el drag solo en el canvas, no en las butacas
            canvas.addEventListener("mousedown", (e) => {
                // Solo iniciar drag si el click es directamente en el canvas o el SVG
                if (e.target === canvas || e.target.tagName === "svg") {
                    this.startDrag(e);
                }
            });

            // Construir el mapa de colores de zona
            this.zoneColorMap = Object.fromEntries(
                this.zones.map((z) => [z.id, z.color])
            );

            const uniqueZones = new Set(
                this.slots.map((s) => s.zone_id).filter((zid) => zid != null)
            );
            this.hasSingleZone = uniqueZones.size === 1;

            // Cargar y renderizar el SVG
            fetch(this.svgUrl)
                .then((r) => r.text())
                .then((svg) => {
                    canvas.innerHTML = svg;
                    this.slotById = Object.fromEntries(
                        this.slots.map((s) => [Number(s.id), s])
                    );
                    this.nodesArray = [...canvas.querySelectorAll(".slot")];
                    this.nodesById = Object.fromEntries(
                        this.nodesArray.map((n) => [+n.dataset.slotId, n])
                    );

                    this.nodesArray.forEach((node, idx) => {
                        const sid = +node.dataset.slotId;
                        if (!this.slotById[sid]) {
                            this.slotById[sid] = {
                                id: sid,
                                name: null,
                                comment: null,
                                status_id: null,
                                zone_id: null,
                            };
                        }
                    });

                    // Pintar cada butaca con color de zona y borde de estado
                    this.nodesArray.forEach((node) => {
                        const sid = +node.dataset.slotId;
                        const slot = this.slotById[sid];
                        const stId = slot.status_id;
                        const free = stId == null;
                        const statusColor = free
                            ? AVAILABLE_STROKE
                            : this.statusColor[stId] ?? "#666";
                        node.style.fill = statusColor;

                        // STROKE: Depende del número de zonas
                        if (this.hasSingleZone) {
                            // Una zona: stroke del estado
                            node.style.stroke = statusColor;
                            node.style.strokeWidth = STROKE_WIDTH;
                        } else {
                            // Múltiples zonas: stroke = color de zona (más fino)
                            const zoneColor =
                                this.zoneColorMap[slot.zone_id] || "#cccccc";
                            node.style.stroke = zoneColor;
                            node.style.strokeWidth = 1;
                        }

                        node.style.cursor = "pointer";

                        // Event listeners para el popover
                        node.addEventListener("mouseenter", (e) => {
                            this.showPopover(node, e);
                        });

                        node.addEventListener("mousemove", (e) => {
                            // Solo actualizar posición, no volver a mostrar
                            if (
                                this.currentPopoverNode === node &&
                                this.popoverEl
                            ) {
                                this.positionPopover(e);
                            }
                        });

                        node.addEventListener("mouseleave", () => {
                            // Solo ocultar si realmente estamos saliendo de este nodo
                            if (this.currentPopoverNode === node) {
                                this.hidePopover();
                            }
                        });

                        node.addEventListener("click", (ev) => {
                            // Prevenir que el click en la butaca inicie el drag
                            ev.stopPropagation();

                            // Ocultar popover al hacer click
                            this.hidePopover();

                            if (ev.shiftKey && this.lastIndex !== null) {
                                const [from, to] =
                                    this.lastIndex < idx
                                        ? [this.lastIndex, idx]
                                        : [idx, this.lastIndex];
                                for (let i = from; i <= to; i++) {
                                    this.toggleNode(this.nodesArray[i], true);
                                }
                            } else if (ev.ctrlKey || ev.metaKey) {
                                this.toggleNode(node, !this.selected.has(sid));
                            } else {
                                this.clearSelection();
                                this.toggleNode(node, true);
                            }
                            this.lastIndex = idx;
                            this.writeHidden();
                        });
                    });

                    // Botones del modal
                    document
                        .querySelector(".selection-btn-edit")
                        ?.addEventListener("click", () => this.openEditModal());
                    document
                        .querySelector("#setSlotProperties .btn-set")
                        ?.addEventListener("click", () =>
                            this.applyModalChanges()
                        );

                    // Zoom si está habilitado
                    if (layout.dataset.zoomEnabled === "1") {
                        const svgEl = canvas.querySelector("svg");
                        this.panzoom = Panzoom(svgEl, {
                            maxScale: 5,
                            minScale: 0.5,
                            step: 0.3,
                            contain: "outside",
                            disablePan: true, // ← desactivamos el paneo por completo
                            cursor: "", // ← anula el cursor "move"
                            handleStartEvent: (e) => {
                                // No iniciar panzoom si estamos arrastrando
                                if (this.isDragging) return false;
                            },
                        });

                        // sólo zoom con rueda y botones:
                        svgEl.parentElement.addEventListener("wheel", (e) => {
                            if (!this.isDragging) {
                                this.panzoom.zoomWithWheel(e);
                            }
                        });
                        document
                            .querySelector(".btn-zoom-in")
                            ?.addEventListener("click", () =>
                                this.panzoom.zoomIn()
                            );
                        document
                            .querySelector(".btn-zoom-out")
                            ?.addEventListener("click", () =>
                                this.panzoom.zoomOut()
                            );
                        document
                            .querySelector(".btn-reset-zoom")
                            ?.addEventListener("click", () =>
                                this.panzoom.reset()
                            );
                    }
                })
                .catch(console.error);
        },
    };

    // Montaje
    const layout = document.getElementById("svg-layout");
    const canvas = document.getElementById("svg-canvas");
    if (layout && canvas) {
        Vue.createApp(SvgLayout, {
            svgUrl: layout.dataset.svgUrl,
            slots: JSON.parse(layout.dataset.slots),
            zones: JSON.parse(layout.dataset.zones),
            configId: parseInt(layout.dataset.configId, 10),
        }).mount(canvas);
    }
})();
