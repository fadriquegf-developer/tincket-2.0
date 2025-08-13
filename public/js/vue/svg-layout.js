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
                if (e.button !== 0 || e.target.classList.contains('slot')) return;
                
                // Prevenir el comportamiento por defecto
                e.preventDefault();
                
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
                    height: "0px"
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

                // 3) Rellenamos el formulario
                $("#set-slot-id").val(
                    slotsSel.length === 1 ? slotsSel[0].id : "Múltiples"
                );

                // ── Estado
                $("#set-slot-status").val(
                    statusCommon !== null ? statusCommon : "null"
                );

                // ── Comentario (lo dejamos vacío siempre para no machacar sin querer)
                $('#setSlotProperties input[name="comment"]').val("");

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

                    node.style.stroke =
                        newStatus === null
                            ? AVAILABLE_STROKE
                            : this.statusColor[newStatus] ?? "#666";

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
        },

        mounted() {
            const canvas = this.$refs.canvas;
            
            // Evento para iniciar el drag solo en el canvas, no en las butacas
            canvas.addEventListener("mousedown", (e) => {
                // Solo iniciar drag si el click es directamente en el canvas o el SVG
                if (e.target === canvas || e.target.tagName === 'svg') {
                    this.startDrag(e);
                }
            });

            // Construir el mapa de colores de zona
            this.zoneColorMap = Object.fromEntries(
                this.zones.map((z) => [z.id, z.color])
            );

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
                        const fillColor =
                            this.zoneColorMap[slot.zone_id] || "#cccccc";
                        node.style.fill = fillColor;

                        const stId = slot.status_id;
                        const free = stId == null;
                        node.style.stroke = free
                            ? AVAILABLE_STROKE
                            : this.statusColor[stId] ?? "#666";
                        node.style.strokeWidth = STROKE_WIDTH;

                        node.style.cursor = "pointer";
                        node.addEventListener("click", (ev) => {
                            // Prevenir que el click en la butaca inicie el drag
                            ev.stopPropagation();
                            
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
                        svgEl.parentElement.addEventListener(
                            "wheel",
                            (e) => {
                                if (!this.isDragging) {
                                    this.panzoom.zoomWithWheel(e);
                                }
                            }
                        );
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