/* ------------------------------------------------------------------
 *  Vue 3 · Plano de butacas · selección y edición masiva
 * ----------------------------------------------------------------*/
(function () {
    const STATUS_COLORS = {
        null: "#a3d165", // libre
        1: "#ffa500",    // disponible
        2: "#e53935",    // vendida
        3: "#800080",    // reservada
        4: "#7b68ee",    // pack
        5: "#ffffff",    // oculta
        6: "#696969",    // bloqueada
        7: "#b9b9b9",    // covid19
        8: "#0368ae",    // discapacidad
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
                /* nodos y datos */
                nodesArray: [],
                nodesById: {},
                slotById: {},
                statusColor: STATUS_COLORS,
                zoneColorMap: {},   // mapa zone_id → color
                changed: {},        // id → payload para backend
            };
        },

        methods: {
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
                this.nodesArray.forEach(n => n.classList.remove("selected"));
                this.selected.clear();
            },

            /* escribir ids o cambios en el <input hidden> */
            writeHidden(payload = [...this.selected]) {
                document.getElementById("slot_labels_input").value =
                    JSON.stringify(payload);
            },

            /* drag-select */
            startDrag(e) {
                if (e.button !== 0) return;
                this.isDragging = true;
                const r = this.$refs.canvas.getBoundingClientRect();
                this.dragStartX = e.clientX - r.left;
                this.dragStartY = e.clientY - r.top;
                this.dragRectEl = document.createElement("div");
                this.dragRectEl.className = "drag-select-rect";
                Object.assign(this.dragRectEl.style, {
                    left: this.dragStartX + "px",
                    top: this.dragStartY + "px",
                });
                this.$refs.canvas.appendChild(this.dragRectEl);
                window.addEventListener("mousemove", this.onDrag);
                window.addEventListener("mouseup", this.stopDrag);
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
                Object.assign(this.dragRectEl.style, { left: x + "px", top: y + "px", width: w + "px", height: h + "px" });
            },
            stopDrag(e) {
                if (!this.isDragging) return;
                this.isDragging = false;
                window.removeEventListener("mousemove", this.onDrag);
                window.removeEventListener("mouseup", this.stopDrag);
                const rect = this.dragRectEl.getBoundingClientRect();
                const add = e.ctrlKey || e.metaKey;
                if (!add) this.clearSelection();
                this.nodesArray.forEach(node => {
                    const b = node.getBoundingClientRect();
                    const inside =
                        b.left >= rect.left &&
                        b.right <= rect.right &&
                        b.top >= rect.top &&
                        b.bottom <= rect.bottom;
                    if (inside) this.toggleNode(node, true);
                });
                this.dragRectEl.remove();
                this.dragRectEl = null;
                this.writeHidden();
            },

            /* abrir modal de edición */
            openEditModal() {
                if (this.selected.size === 0) {
                    alert("Selecciona al menos una butaca.");
                    return;
                }
            
                // ← ZONAS: vaciamos y poblamos el select cada vez que abrimos el modal
                const zoneSelect = document.getElementById("set-slot-zone");
                zoneSelect.innerHTML = '<option value="">-- Selecciona zona --</option>';
                this.zones.forEach(z => {
                    const opt = document.createElement("option");
                    opt.value = z.id;
                    opt.textContent = z.name;
                    zoneSelect.appendChild(opt);
                });
            
                const one = this.selected.size === 1 ? [...this.selected][0] : null;
                if (one) {
                    const slot = this.slotById[one] ?? (this.slotById[one] = {});
                    $("#set-slot-id").val(one);
                    $("#set-slot-status").val(slot.status_id ?? "null");
                    $('#setSlotProperties input[name="comment"]').val(slot.comment ?? "");
                    $("#set-slot-name").prop("disabled", false).val(slot.name ?? "");
                    $("#set-slot-x").val(slot.x ?? "");
                    $("#set-slot-y").val(slot.y ?? "");
                    // ← ZONAS: seleccionamos la zona actual del slot
                    $("#set-slot-zone").val(slot.zone_id ?? "");
                } else {
                    $("#set-slot-id").val("Múltiples");
                    $("#set-slot-status").val("null");
                    $('#setSlotProperties input[name="comment"]').val("");
                    $("#set-slot-name").prop("disabled", true).val("");
                    $("#set-slot-zone").val("");
                }
            
                $("#setSlotProperties").modal("show");
            },

            /* aplicar cambios del modal */
            applyModalChanges() {
                const statusVal = $("#set-slot-status").val();
                const newStatus = statusVal === "null" ? null : +statusVal;
                const newComment = $('#setSlotProperties input[name="comment"]').val();
                const newName = $("#set-slot-name").prop("disabled") ? null : $("#set-slot-name").val().trim();
                const newX = parseFloat($("#set-slot-x").val());
                const newY = parseFloat($("#set-slot-y").val());
                const newZone = parseInt($("#set-slot-zone").val(), 10) || null;

                this.selected.forEach(id => {
                    const node = this.nodesById[id];
                    const slot = this.slotById[id];

                    if (newName) slot.name = newName;
                    slot.status_id = newStatus;
                    slot.comment = newComment;
                    slot.x = isNaN(newX) ? null : newX;
                    slot.y = isNaN(newY) ? null : newY;
                    slot.zone_id = newZone;

                    node.style.stroke = newStatus === null
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

                document.getElementById("slot_labels_input").value = JSON.stringify(Object.values(this.changed));
                $("#setSlotProperties").modal("hide");
            },
        },

        mounted() {
            const canvas = this.$refs.canvas;
            canvas.addEventListener("mousedown", this.startDrag);

            // Construir el mapa de colores de zona
            this.zoneColorMap = Object.fromEntries(
                this.zones.map(z => [z.id, z.color])
            );

            // Cargar y renderizar el SVG
            fetch(this.svgUrl)
                .then(r => r.text())
                .then(svg => {
                    canvas.innerHTML = svg;
                    this.slotById = Object.fromEntries(this.slots.map(s => [Number(s.id), s]));
                    this.nodesArray = [...canvas.querySelectorAll(".slot")];
                    this.nodesById = Object.fromEntries(this.nodesArray.map(n => [+n.dataset.slotId, n]));

                    this.nodesArray.forEach((node, idx) => {
                        const sid = +node.dataset.slotId;
                        if (!this.slotById[sid]) {
                            this.slotById[sid] = { id: sid, name: null, comment: null, status_id: null, zone_id: null };
                        }
                    });

                    // Pintar cada butaca con color de zona y borde de estado
                    this.nodesArray.forEach(node => {
                        const sid = +node.dataset.slotId;
                        const slot = this.slotById[sid];
                        const fillColor = this.zoneColorMap[slot.zone_id] || '#cccccc';
                        node.style.fill = fillColor;

                        const stId = slot.status_id;
                        const free = stId == null;
                        node.style.stroke = free
                            ? AVAILABLE_STROKE
                            : this.statusColor[stId] ?? "#666";
                        node.style.strokeWidth = STROKE_WIDTH;

                        node.style.cursor = "pointer";
                        node.addEventListener("click", ev => {
                            if (this.isDragging) return;
                            if (ev.shiftKey && this.lastIndex !== null) {
                                const [from, to] = this.lastIndex < idx
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
                    document.querySelector(".selection-btn-edit")
                        .addEventListener("click", () => this.openEditModal());
                    document.querySelector("#setSlotProperties .btn-set")
                        .addEventListener("click", () => this.applyModalChanges());

                    // Zoom si está habilitado
                    if (layout.dataset.zoomEnabled === "1") {
                        const svgEl = canvas.querySelector("svg");
                        this.panzoom = Panzoom(svgEl, {
                          maxScale: 5,
                          minScale: 0.5,
                          step: 0.3,
                          contain: "outside",
                          disablePan: true,           // ← desactivamos el paneo por completo
                          cursor: "",                 // ← anula el cursor "move"
                          handleStartEvent: e => {},  // ← no interferir con tu drag-select
                        });
                      
                        // sólo zoom con rueda y botones:
                        svgEl.parentElement.addEventListener("wheel", this.panzoom.zoomWithWheel);
                        document.querySelector(".btn-zoom-in")
                                .addEventListener("click", () => this.panzoom.zoomIn());
                        document.querySelector(".btn-zoom-out")
                                .addEventListener("click", () => this.panzoom.zoomOut());
                        document.querySelector(".btn-reset-zoom")
                                .addEventListener("click", () => this.panzoom.reset());
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
