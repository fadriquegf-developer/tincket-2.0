(function () {
    const STATUS_COLORS = {
        null: "#a3d165",
        1: "#ffa500",
        2: "#e53935",
        3: "#800080",
        4: "#7b68ee",
        5: "#ffffff",
        6: "#696969",
        7: "#b9b9b9",
        8: "#0368ae",
    };

    const AVAILABLE_STROKE = "#008102";
    const STROKE_WIDTH = 2;

    const SvgLayout = {
        template: `<div ref="canvas" class="svg-layout"></div>`,

        props: {
            svgUrl: String,
            slots: Array,
            zones: Array,
            configId: Number,
        },

        data() {
            return {
                selected: new Set(),
                lastIndex: null,
                isDragging: false,
                dragRectEl: null,
                nodesArray: [],
                nodesById: {},
                slotById: {},
                statusColor: STATUS_COLORS,
                zoneColorMap: {},
                changed: {},
                popoverEl: null,
                currentPopoverNode: null,
                popoverTimeout: null,
                hasSingleZone: false,
            };
        },
        methods: {
            toggleNode(node, sel) {
                const id = +node.dataset.slotId;
                const slot = this.slotById[id];
                if (slot.status_id === 2) return; // no seleccionar vendidos

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
            writeHidden(payload = Object.values(this.changed)) {
                document.getElementById("slot_labels_input").value =
                    JSON.stringify(payload);
            },
            startDrag(e) {
                if (e.button !== 0) return;
                e.preventDefault();

                // Ocultar popover al iniciar drag
                this.hidePopover();

                // si quedara alguno colgado, límpialo antes
                if (this.dragRectEl) {
                    try {
                        this.dragRectEl.remove();
                    } catch (_) {}
                    this.dragRectEl = null;
                }

                this.isDragging = true;
                const r = this.$refs.canvas.getBoundingClientRect();
                this.dragStartX = e.clientX - r.left;
                this.dragStartY = e.clientY - r.top;

                this.dragRectEl = document.createElement("div");
                this.dragRectEl.className = "drag-select-rect";
                Object.assign(this.dragRectEl.style, {
                    left: this.dragStartX + "px",
                    top: this.dragStartY + "px",
                    width: 0,
                    height: 0,
                });
                this.$refs.canvas.appendChild(this.dragRectEl);

                // 🆕 Guardar selección previa para restaurar si es necesario
                this.previewSelection = new Set();

                window.addEventListener("mousemove", this.onDrag, {
                    passive: true,
                });
                window.addEventListener("mouseup", this.stopDrag, {
                    passive: true,
                });
                // salidas "raras"
                window.addEventListener("blur", this.stopDrag, {
                    passive: true,
                });
                this.$refs.canvas.addEventListener(
                    "mouseleave",
                    this.stopDrag,
                    { passive: true }
                );
                document.addEventListener(
                    "visibilitychange",
                    this._visStopDrag,
                    { passive: true }
                );
            },
            onDrag(e) {
                if (!this.isDragging) return;
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

                // 🆕 Actualizar preview de selección en tiempo real
                const rect = this.dragRectEl.getBoundingClientRect();

                // Limpiar preview anterior
                this.previewSelection.forEach((id) => {
                    const node = this.nodesById[id];
                    if (node && !this.selected.has(id)) {
                        node.classList.remove("preview-selected");
                    }
                });
                this.previewSelection.clear();

                // Marcar nuevos candidatos
                this.nodesArray.forEach((node) => {
                    const id = +node.dataset.slotId;
                    const slot = this.slotById[id];

                    // No previsualizar las vendidas
                    if (slot.status_id === 2) return;

                    const b = node.getBoundingClientRect();
                    const intersects = !(
                        b.right < rect.left ||
                        b.left > rect.right ||
                        b.bottom < rect.top ||
                        b.top > rect.bottom
                    );

                    if (intersects) {
                        this.previewSelection.add(id);
                        if (!this.selected.has(id)) {
                            node.classList.add("preview-selected");
                        }
                    } else if (!this.selected.has(id)) {
                        node.classList.remove("preview-selected");
                    }
                });
            },
            _visStopDrag() {
                if (document.visibilityState === "hidden") {
                    this.stopDrag();
                }
            },
            stopDrag(e) {
                if (!this.isDragging) return;
                this.isDragging = false;

                window.removeEventListener("mousemove", this.onDrag);
                window.removeEventListener("mouseup", this.stopDrag);
                window.removeEventListener("blur", this.stopDrag);
                this.$refs.canvas?.removeEventListener(
                    "mouseleave",
                    this.stopDrag
                );
                document.removeEventListener(
                    "visibilitychange",
                    this._visStopDrag
                );

                // si había rectángulo, úsalo y LÍMPIALO
                if (this.dragRectEl) {
                    const rect = this.dragRectEl.getBoundingClientRect();
                    const add = e && (e.ctrlKey || e.metaKey);
                    if (!add) this.clearSelection();

                    this.nodesArray.forEach((node) => {
                        const b = node.getBoundingClientRect();

                        // Detectar intersección parcial
                        const intersects = !(
                            b.right < rect.left ||
                            b.left > rect.right ||
                            b.bottom < rect.top ||
                            b.top > rect.bottom
                        );

                        if (intersects) {
                            this.toggleNode(node, true);
                        }

                        // 🆕 Limpiar clase de preview
                        node.classList.remove("preview-selected");
                    });

                    try {
                        this.dragRectEl.remove();
                    } catch (_) {}
                    this.dragRectEl = null;
                }

                // 🆕 Limpiar previewSelection
                this.previewSelection.clear();

                this.writeHidden();
            },

            // Métodos para el popover
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

                // Si está vendida (status_id === 2) y tiene cart_id, mostrar código
                if (stId === 2 && slot.confirmation_code) {
                    const locale = window.currentLocale || "es";
                    const clickToCopy =
                        window.sessionTranslations?.click_to_copy?.[locale] ||
                        "Click para copiar";

                    content += `<div style="color: #ffffff; font-size: 13px; font-weight: 600; margin-bottom: 5px;">
                        🛒 <span>${this.escapeHtml(
                            slot.confirmation_code
                        )}</span>
                        <div style="font-size: 11px; color: #aaa; margin-top: 4px;">${clickToCopy}</div>
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

                // ocultar campos innecesarios
                $("#set-slot-name").parent().hide();
                $("#set-slot-x").parent().hide();
                $("#set-slot-y").parent().hide();
                $("#set-slot-zone").parent().hide();

                const ids = this._selectedIds();
                const one = ids.length === 1 ? ids[0] : null;

                // Estado común (si todas comparten el mismo)
                const { mixed: mixedStatus, value: commonStatus } =
                    this._commonOf((s) => s.status_id ?? null);
                // Comentario común (si todos igual)
                const { mixed: mixedComment, value: commonComment } =
                    this._commonOf((s) => s.comment ?? "");

                if (one) {
                    $("#set-slot-id").val(one);
                } else {
                    $("#set-slot-id").val("Múltiples");
                }

                // Si hay estado común, ponlo; si no, deja "null" o una opción "variado"
                if (!mixedStatus) {
                    $("#set-slot-status").val(
                        commonStatus === null ? "null" : String(commonStatus)
                    );
                } else {
                    // Opción 1: dejarlo sin cambiar (null)
                    $("#set-slot-status").val("null");
                    // Opción 2 (si quieres mostrarlo): asegura una opción <option value="__mixed__">— variado —</option>
                    // $("#set-slot-status").val("__mixed__");
                }

                // Comentario
                $('#setSlotProperties input[name="comment"]').val(
                    !mixedComment ? commonComment : ""
                );

                $("#setSlotProperties").modal("show");
            },
            applyModalChanges() {
                const statusVal = $("#set-slot-status").val();
                const newStatus = statusVal === "null" ? null : +statusVal;
                const newComment = $(
                    '#setSlotProperties input[name="comment"]'
                ).val();

                this.selected.forEach((id) => {
                    const node = this.nodesById[id];
                    const slot = this.slotById[id];

                    if (slot.status_id === 2) return;

                    slot.status_id = newStatus;
                    slot.comment = newComment;

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
                        node.style.strokeWidth = 1;
                    }

                    this.changed[id] = {
                        id,
                        comment: slot.comment ?? null,
                        status_id: slot.status_id ?? null,
                    };
                });

                this.writeHidden();
                $("#setSlotProperties").modal("hide");
            },
            _selectedIds() {
                return [...this.selected];
            },

            _commonOf(getter) {
                const ids = this._selectedIds();
                if (ids.length === 0) return { mixed: false, value: null };

                let first = undefined;
                for (const id of ids) {
                    const slot = this.slotById[id] ?? {};
                    const v = getter(slot);
                    if (first === undefined) first = v;
                    else if (v !== first) return { mixed: true, value: null };
                }
                return { mixed: false, value: first ?? null };
            },
        },
        mounted() {
            const canvas = this.$refs.canvas;
            canvas.addEventListener("mousedown", this.startDrag);

            // si se abre un modal/escape, limpia
            document.addEventListener("keydown", (e) => {
                if (e.key === "Escape") {
                    this.stopDrag(e);
                    this.hidePopover();
                }
            });

            this.zoneColorMap = Object.fromEntries(
                this.zones.map((z) => [z.id, z.color])
            );

            const uniqueZones = new Set(
                this.slots.map((s) => s.zone_id).filter((zid) => zid != null)
            );

            this.hasSingleZone = uniqueZones.size === 1;

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
                        const slot =
                            this.slotById[sid] ??
                            (this.slotById[sid] = { id: sid });

                        const stId = slot.status_id;
                        const free = stId == null;
                        const statusColor = free
                            ? AVAILABLE_STROKE
                            : this.statusColor[stId] ?? "#666";
                        node.style.fill = statusColor;

                        // STROKE: Depende del número de zonas
                        if (this.hasSingleZone) {
                            // Una zona: stroke verde o del estado si está bloqueado
                            node.style.stroke = free
                                ? AVAILABLE_STROKE
                                : statusColor;
                            node.style.strokeWidth = STROKE_WIDTH;
                        } else {
                            // Múltiples zonas: stroke = color de zona
                            const zoneColor =
                                this.zoneColorMap[slot.zone_id] || "#cccccc";
                            node.style.stroke = zoneColor;
                            node.style.strokeWidth = 1;
                        }

                        node.style.cursor =
                            slot.status_id === 2 ? "not-allowed" : "pointer";

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
                            if (this.isDragging) return;

                            // 🔥 Si es vendida, copiar al portapapeles
                            if (
                                slot.status_id === 2 &&
                                slot.confirmation_code
                            ) {
                                ev.stopPropagation();
                                this.hidePopover();

                                // Método de copiar con fallback
                                const copyToClipboard = (text) => {
                                    if (
                                        navigator.clipboard &&
                                        navigator.clipboard.writeText
                                    ) {
                                        return navigator.clipboard.writeText(
                                            text
                                        );
                                    }

                                    // Fallback
                                    return new Promise((resolve, reject) => {
                                        const textArea =
                                            document.createElement("textarea");
                                        textArea.value = text;
                                        textArea.style.position = "fixed";
                                        textArea.style.left = "-999999px";
                                        textArea.style.top = "-999999px";
                                        document.body.appendChild(textArea);
                                        textArea.focus();
                                        textArea.select();

                                        try {
                                            const successful =
                                                document.execCommand("copy");
                                            document.body.removeChild(textArea);
                                            if (successful) {
                                                resolve();
                                            } else {
                                                reject(
                                                    new Error(
                                                        "execCommand failed"
                                                    )
                                                );
                                            }
                                        } catch (err) {
                                            document.body.removeChild(textArea);
                                            reject(err);
                                        }
                                    });
                                };

                                copyToClipboard(slot.confirmation_code)
                                    .then(() => {
                                        const locale =
                                            window.currentLocale || "es";
                                        const successMsg =
                                            window.sessionTranslations
                                                ?.code_copied?.[locale] ||
                                            "Código copiado";

                                        new Noty({
                                            type: "success",
                                            text: `<strong>${successMsg}:</strong> ${slot.confirmation_code}`,
                                        }).show();
                                    })
                                    .catch((err) => {
                                        console.error(
                                            "❌ Error copiando:",
                                            err
                                        );

                                        const locale =
                                            window.currentLocale || "es";
                                        const errorMsg =
                                            window.sessionTranslations
                                                ?.copy_error?.[locale] ||
                                            "Error al copiar";

                                        new Noty({
                                            type: "error",
                                            text: `<strong>${errorMsg}.</strong> `,
                                        }).show();
                                    });
                                return;
                            }

                            // Ocultar popover al hacer click
                            this.hidePopover();

                            if (ev.shiftKey && this.lastIndex !== null) {
                                const [from, to] =
                                    this.lastIndex < idx
                                        ? [this.lastIndex, idx]
                                        : [idx, this.lastIndex];
                                for (let i = from; i <= to; i++) {
                                    const n = this.nodesArray[i];
                                    this.toggleNode(n, true);
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

                    document
                        .querySelector(".selection-btn-edit")
                        .addEventListener("click", () => this.openEditModal());
                    document
                        .querySelector("#setSlotProperties .btn-set")
                        .addEventListener("click", () =>
                            this.applyModalChanges()
                        );

                    if (layout.dataset.zoomEnabled === "1") {
                        const svgEl = canvas.querySelector("svg");
                        this.panzoom = Panzoom(svgEl, {
                            maxScale: 5,
                            minScale: 0.5,
                            step: 0.3,
                            contain: "outside",
                            disablePan: true,
                            cursor: "",
                            handleStartEvent: (e) => {},
                        });

                        svgEl.parentElement.addEventListener(
                            "wheel",
                            this.panzoom.zoomWithWheel
                        );
                        document
                            .querySelector(".btn-zoom-in")
                            .addEventListener("click", () =>
                                this.panzoom.zoomIn()
                            );
                        document
                            .querySelector(".btn-zoom-out")
                            .addEventListener("click", () =>
                                this.panzoom.zoomOut()
                            );
                        document
                            .querySelector(".btn-reset-zoom")
                            .addEventListener("click", () =>
                                this.panzoom.reset()
                            );
                    }
                })
                .catch(console.error);
        },
        beforeUnmount() {
            // Limpiar popover al destruir el componente
            if (this.popoverEl) {
                this.popoverEl.remove();
                this.popoverEl = null;
            }
            clearTimeout(this.popoverTimeout);
        },
    };

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
