/* ==========================================================================
   UPLOAD LOGIC & PROGRESS BAR
   ========================================================================== */
const dropZone = document.getElementById("dropZone");
const fileIn   = document.getElementById("fileIn");
const upBtn    = document.getElementById("upBtn");
let picked     = null; 

// Xử lý Drag & Drop
["dragover","dragenter"].forEach(evt =>
    dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add("over"); })
);
["dragleave","drop"].forEach(evt =>
    dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove("over"); })
);

dropZone.addEventListener("drop",  ev => pick(ev.dataTransfer.files[0]));
fileIn.addEventListener("change",  ev => pick(ev.target.files[0]));

function pick(file) {
    if (!file || !file.type.startsWith("video/")) { alert("Please select a video file!"); return; }
    if (file.size > 500 * 1024 * 1024) { alert("File exceeds 500MB limit!"); return; }
    
    picked = file;
    const info = document.getElementById("fileInfo");
    info.innerHTML = `<strong>${file.name}</strong> — ${(file.size/1024/1024).toFixed(1)} MB`;
    info.style.display = "block";
    
    const t = document.getElementById("titleIn");
    if (!t.value) t.value = file.name.replace(/\.[^.]+$/, "");
}

// Xử lý Upload qua XHR
upBtn.addEventListener("click", () => {
    if (!picked) { alert("No file selected!"); return; }
    const title = document.getElementById("titleIn").value.trim();
    if (!title) { alert("Title is required!"); return; }

    const fd = new FormData();
    fd.append("video", picked);
    fd.append("title", title);
    fd.append("description", document.getElementById("descIn").value.trim());

    upBtn.disabled = true;
    upBtn.textContent = "Uploading...";
    document.getElementById("progWrap").style.display = "block";
    setMsg("", ""); 

    const t0  = Date.now();
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/videohub/api/videos/upload.php");

    xhr.upload.onprogress = e => {
        if (!e.lengthComputable) return;
        const pct     = Math.round(e.loaded / e.total * 100);
        const elapsed = (Date.now() - t0) / 1000;
        const spd     = (e.loaded / 1024 / 1024 / elapsed).toFixed(1);
        const eta     = Math.max(0, Math.round((e.total-e.loaded)/(e.loaded/elapsed)));
        
        document.getElementById("progFill").style.width = pct + "%";
        document.getElementById("progPct").textContent  = pct + "%";
        document.getElementById("progSpd").textContent  = spd + " MB/s";
        document.getElementById("progEta").textContent  = "~" + eta + "s left";
    };

    xhr.onload = () => {
        const elapsed = ((Date.now() - t0) / 1000).toFixed(1);
        try {
            const d = JSON.parse(xhr.responseText);
            if (d.success) {
                setMsg("ok", `Upload completed in ${elapsed}s — processing...`);
                pollStatus(d.video_id);
            } else {
                setMsg("err", "Error: " + d.message);
                resetBtn();
            }
        } catch(e) {
            setMsg("err", "Parse error: " + xhr.responseText.slice(0, 100));
            resetBtn();
        }
    };
    xhr.onerror = () => { setMsg("err", "Network error!"); resetBtn(); };
    xhr.send(fd);
});

function resetBtn() {
    upBtn.disabled = false;
    upBtn.textContent = "Upload Video";
}

function setMsg(type, text) {
    const el = document.getElementById("msg");
    el.className = "msg" + (type ? " msg-" + type : "");
    el.innerHTML = text;
    el.style.display = text ? "block" : "none";
}

// Polling trạng thái xử lý
function pollStatus(videoId) {
    const interval = setInterval(async () => {
        try {
            const d = await fetch("/videohub/api/videos/status.php?id=" + videoId).then(r => r.json());
            if (d.status === "ready") {
                clearInterval(interval);
                setMsg("ok", `Done! <a href="watch.html?id=${videoId}">Watch now &rarr;</a>`);
                resetBtn();
            }
            if (d.status === "error") {
                clearInterval(interval);
                setMsg("err", "Processing failed. Check log at /tmp/ffmpeg_" + videoId + ".log");
                resetBtn();
            }
        } catch(e) {}
    }, 3000);
}
