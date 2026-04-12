// File: frontend/js/upload.js
const dropZone    = document.getElementById('dropZone');
const fileInput   = document.getElementById('fileInput');
const uploadBtn   = document.getElementById('uploadBtn');
const titleInput  = document.getElementById('titleInput');
const descInput   = document.getElementById('descInput');
const progressWrap= document.getElementById('progressWrap');
const progressFill= document.getElementById('progressFill');
const progressPct = document.getElementById('progressPct');
const progressSpd = document.getElementById('progressSpeed');
const progressEta = document.getElementById('progressEta');
const statusMsg   = document.getElementById('statusMsg');
let selectedFile  = null;
const latencyData = [];

 
// Xu ly Drag & Drop
['dragover','dragenter'].forEach(evt =>
  dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('drag-over'); }));
['dragleave','drop'].forEach(evt =>
  dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('drag-over'); }));
dropZone.addEventListener('drop', e => handleFile(e.dataTransfer.files[0]));
fileInput.addEventListener('change', e => handleFile(e.target.files[0]));
 
function handleFile(file) {
  if (!file) return;
  if (!file.type.startsWith('video/')) { alert('Vui long chon file VIDEO!'); return; }
  if (file.size > 500 * 1024 * 1024) { alert('File qua lon! Toi da 500MB.'); return; }
  selectedFile = file;
  const info = document.getElementById('fileInfo');
  info.innerHTML = '<b>' + file.name + '</b> &mdash; ' + (file.size/1024/1024).toFixed(1) + ' MB';
  info.hidden = false;
  // Tu dong dien tieu de neu trong
  if (!titleInput.value.trim()) {
    titleInput.value = file.name.replace(/\.[^.]+$/, '');
  }
}
 
uploadBtn.addEventListener('click', () => {
  if (!selectedFile) { alert('Chua chon file video!'); return; }
  if (!titleInput.value.trim()) { alert('Nhap tieu de video!'); titleInput.focus(); return; }
 
  const formData = new FormData();
  formData.append('video',       selectedFile);
  formData.append('title',       titleInput.value.trim());
  formData.append('description', descInput.value.trim());
 
  uploadBtn.disabled = true;
  uploadBtn.textContent = 'Dang upload...';
  progressWrap.hidden = false;
  statusMsg.hidden = true;
 
  const startTime = Date.now();
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/videos/upload.php');
 
  xhr.upload.addEventListener('progress', e => {
    if (!e.lengthComputable) return;
    const pct = Math.round(e.loaded / e.total * 100);
    const elapsed = (Date.now() - startTime) / 1000;
    const speedMBps = (e.loaded / 1024 / 1024 / elapsed).toFixed(1);
    const etaSec = Math.round((e.total - e.loaded) / (e.loaded / elapsed));
    progressFill.style.width = pct + '%';
    progressPct.textContent  = pct + '%';
    progressSpd.textContent  = speedMBps + ' MB/s';
    progressEta.textContent  = 'Con lai: ' + etaSec + 's';
  });
 
  xhr.addEventListener('load', () => {
    const totalMs = Date.now() - startTime;
  const fileMB  = selectedFile.size / 1024 / 1024;
  const record  = {
    fileName:    selectedFile.name,
    fileSizeMB:  fileMB.toFixed(2),
    durationMs:  totalMs,
    throughput:  (fileMB / (totalMs / 1000)).toFixed(2) + ' MB/s',
    timestamp:   new Date().toISOString(),
  };
  latencyData.push(record);
  console.table(latencyData);  // Xem trong DevTools > Console
  localStorage.setItem('latencyData', JSON.stringify(latencyData));

    const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
    try {
      const data = JSON.parse(xhr.responseText);
      if (data.success) {
        showStatus('success', 'Upload xong sau ' + elapsed + 's! Dang xu ly video...');
        pollStatus(data.video_id);
      } else {
        showStatus('error', 'Loi: ' + data.message);
        resetBtn();
      }
    } catch (e) {
      showStatus('error', 'Loi parse response: ' + xhr.responseText.substring(0, 200));
      resetBtn();
    }
  });
 
  xhr.addEventListener('error', () => { showStatus('error', 'Loi ket noi!'); resetBtn(); });
  xhr.send(formData);
});
 
function resetBtn() {
  uploadBtn.disabled = false;
  uploadBtn.textContent = 'Upload Video';
}
 
function showStatus(type, msg) {
  statusMsg.className = 'status-msg status-' + type;
  statusMsg.innerHTML = msg;
  statusMsg.hidden = false;
}
 
function pollStatus(videoId) {
  showStatus('info', 'Dang xu ly video (transcode + thumbnail)... Vui long cho...');
  const interval = setInterval(async () => {
    try {
      const res  = await fetch('/api/videos/status.php?id=' + videoId);
      const data = await res.json();
      if (data.status === 'ready') {
        clearInterval(interval);
        showStatus('success', 'Video san sang! <a href="watch.html?id=' + videoId + '">Xem ngay</a>');
        resetBtn();
      } else if (data.status === 'error') {
        clearInterval(interval);
        showStatus('error', 'Xu ly video that bai. Kiem tra log FFmpeg tai /tmp/ffmpeg_' + videoId + '.log');
        resetBtn();
      }
    } catch (e) { /* bo qua loi mang tam thoi */ }
  }, 3000);
}
