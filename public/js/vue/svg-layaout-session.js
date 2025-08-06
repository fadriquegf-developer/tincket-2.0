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
                this.nodesArray.forEach(n => n.classList.remove("selected"));
                this.selected.clear();
            },
            writeHidden(payload = Object.values(this.changed)) {
                document.getElementById("slot_labels_input").value =
                    JSON.stringify(payload);
            },
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

            openEditModal() {
                if (this.selected.size === 0) {
                    alert("Selecciona al menos una butaca.");
                    return;
                }

                // ocultar campos innecesarios
                $("#set-slot-name").parent().hide();
                $("#set-slot-x").parent().hide();
                $("#set-slot-y").parent().hide();
                $("#set-slot-zone").parent().hide();

                const one = this.selected.size === 1 ? [...this.selected][0] : null;
                if (one) {
                    const slot = this.slotById[one] ?? {};
                    $("#set-slot-id").val(one);
                    $("#set-slot-status").val(slot.status_id ?? "null");
                    $('#setSlotProperties input[name="comment"]').val(slot.comment ?? "");
                } else {
                    $("#set-slot-id").val("Múltiples");
                    $("#set-slot-status").val("null");
                    $('#setSlotProperties input[name="comment"]').val("");
                }

                $("#setSlotProperties").modal("show");
            },

            applyModalChanges() {
                const statusVal = $("#set-slot-status").val();
                const newStatus = statusVal === "null" ? null : +statusVal;
                const newComment = $('#setSlotProperties input[name="comment"]').val();

                this.selected.forEach(id => {
                    const node = this.nodesById[id];
                    const slot = this.slotById[id];

                    if (slot.status_id === 2) return;

                    slot.status_id = newStatus;
                    slot.comment = newComment;

                    node.style.stroke = newStatus === null
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
        },

        mounted() {
            const canvas = this.$refs.canvas;
            canvas.addEventListener("mousedown", this.startDrag);

            this.zoneColorMap = Object.fromEntries(
                this.zones.map(z => [z.id, z.color])
            );

            fetch(this.svgUrl)
                .then(r => r.text())
                .then(svg => {
                    canvas.innerHTML = svg;
                    this.slotById = Object.fromEntries(this.slots.map(s => [Number(s.id), s]));
                    this.nodesArray = [...canvas.querySelectorAll(".slot")];
                    this.nodesById = Object.fromEntries(this.nodesArray.map(n => [+n.dataset.slotId, n]));

                    this.nodesArray.forEach((node, idx) => {
                        const sid = +node.dataset.slotId;
                        const slot = this.slotById[sid] ?? (this.slotById[sid] = { id: sid });

                        const fillColor = this.zoneColorMap[slot.zone_id] || '#cccccc';
                        node.style.fill = fillColor;

                        const stId = slot.status_id;
                        const free = stId == null;
                        node.style.stroke = free
                            ? AVAILABLE_STROKE
                            : this.statusColor[stId] ?? "#666";
                        node.style.strokeWidth = STROKE_WIDTH;

                        node.style.cursor = slot.status_id === 2 ? "not-allowed" : "pointer";

                        node.addEventListener("click", ev => {
                            if (this.isDragging || slot.status_id === 2) return;
                            if (ev.shiftKey && this.lastIndex !== null) {
                                const [from, to] = this.lastIndex < idx ? [this.lastIndex, idx] : [idx, this.lastIndex];
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

                    document.querySelector(".selection-btn-edit")
                        .addEventListener("click", () => this.openEditModal());
                    document.querySelector("#setSlotProperties .btn-set")
                        .addEventListener("click", () => this.applyModalChanges());

                    if (layout.dataset.zoomEnabled === "1") {
                        const svgEl = canvas.querySelector("svg");
                        this.panzoom = Panzoom(svgEl, {
                            maxScale: 5,
                            minScale: 0.5,
                            step: 0.3,
                            contain: "outside",
                            disablePan: true,
                            cursor: "",
                            handleStartEvent: e => {},
                        });

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
