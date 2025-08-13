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

                window.addEventListener("mousemove", this.onDrag, {
                    passive: true,
                });
                window.addEventListener("mouseup", this.stopDrag, {
                    passive: true,
                });
                // salidas “raras”
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
                        const inside =
                            b.left >= rect.left &&
                            b.right <= rect.right &&
                            b.top >= rect.top &&
                            b.bottom <= rect.bottom;
                        if (inside) this.toggleNode(node, true);
                    });

                    try {
                        this.dragRectEl.remove();
                    } catch (_) {}
                    this.dragRectEl = null;
                }

                this.writeHidden();
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

                // Si hay estado común, ponlo; si no, deja "null" o una opción “variado”
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

                    node.style.stroke =
                        newStatus === null
                            ? AVAILABLE_STROKE
                            : this.statusColor[newStatus] ?? "#666";

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
                if (e.key === "Escape") this.stopDrag(e);
            });

            this.zoneColorMap = Object.fromEntries(
                this.zones.map((z) => [z.id, z.color])
            );

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

                        const fillColor =
                            this.zoneColorMap[slot.zone_id] || "#cccccc";
                        node.style.fill = fillColor;

                        const stId = slot.status_id;
                        const free = stId == null;
                        node.style.stroke = free
                            ? AVAILABLE_STROKE
                            : this.statusColor[stId] ?? "#666";
                        node.style.strokeWidth = STROKE_WIDTH;

                        node.style.cursor =
                            slot.status_id === 2 ? "not-allowed" : "pointer";

                        node.addEventListener("click", (ev) => {
                            if (this.isDragging || slot.status_id === 2) return;
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
