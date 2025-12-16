<?php
$pageTitle = 'Upload Session - ' . htmlspecialchars($campaign['name']);
ob_start();
?>

<div class="container" style="max-width: 800px;">
    <div class="mb-4">
        <a href="/campaigns/<?= $campaign['id'] ?>" class="text-muted" style="text-decoration: none;">
            ← Back to <?= htmlspecialchars($campaign['name']) ?>
        </a>
    </div>

    <h1 style="font-family: 'Cinzel', serif; font-size: 2.5rem; margin-bottom: 2rem;">
        Upload New Session
    </h1>

    <div class="card">
        <form id="uploadForm" onsubmit="uploadSession(event)" enctype="multipart/form-data">
            <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
            
            <div class="form-group">
                <label class="form-label">Session Title *</label>
                <input type="text" name="title" class="form-input" required 
                       placeholder="e.g., The Dragon's Lair" value="Session <?= $nextSessionNumber ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Session Number</label>
                <input type="number" name="session_number" class="form-input" 
                       value="<?= $nextSessionNumber ?>" min="1">
            </div>
            
            <div class="form-group">
                <label class="form-label">Session Date</label>
                <input type="date" name="session_date" class="form-input" 
                       value="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Audio Recording *</label>
                <div id="dropZone" class="upload-zone">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎙️</div>
                    <p style="font-size: 1.25rem; margin-bottom: 0.5rem;">
                        <strong>Drop audio file here or click to browse</strong>
                    </p>
                    <p class="text-muted">
                        Supported: MP3, MP4, WAV, M4A, WebM<br>
                        Maximum: 500MB / 4 hours
                    </p>
                    <input type="file" id="audioFile" name="audio" accept="audio/*" 
                           style="display: none;" required onchange="handleFileSelect(event)">
                </div>
                
                <div id="fileInfo" style="display: none; margin-top: 1rem;">
                    <div style="padding: 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 0.5rem; border: 1px solid var(--success);">
                        <div class="flex items-center justify-between">
                            <div>
                                <strong id="fileName"></strong><br>
                                <span class="text-muted" id="fileSize"></span>
                            </div>
                            <button type="button" onclick="clearFile()" class="btn btn-secondary">
                                Change
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="uploadProgress" style="display: none;">
                <div class="progress-container">
                    <div id="progressBar" class="progress-bar" style="width: 0%;"></div>
                </div>
                <p class="text-center text-muted" id="progressText">Uploading...</p>
            </div>
            
            <div class="flex gap-2 mt-4">
                <button type="submit" id="submitBtn" class="btn btn-primary">
                    Upload & Start Processing
                </button>
                <a href="/campaigns/<?= $campaign['id'] ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <div class="card mt-4">
        <h3 style="margin-bottom: 1rem;">ℹ️ What happens next?</h3>
        <ol style="margin-left: 1.5rem; color: var(--text-secondary);">
            <li style="margin-bottom: 0.5rem;">Your audio file will be uploaded and queued for processing</li>
            <li style="margin-bottom: 0.5rem;">Whisper will transcribe the audio (this may take a few minutes)</li>
            <li style="margin-bottom: 0.5rem;">Claude will generate a narrative recap and extract entities</li>
            <li style="margin-bottom: 0.5rem;">You'll be notified when processing is complete (typically 5-15 minutes)</li>
        </ol>
        
        <div style="margin-top: 1rem; padding: 1rem; background: rgba(245, 158, 11, 0.1); border-radius: 0.5rem;">
            <strong>💡 Tip:</strong> Make sure your audio quality is good for best transcription results. 
            Background music is okay, but avoid excessive noise.
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('audioFile');

// Click to browse
dropZone.addEventListener('click', () => fileInput.click());

// Drag and drop handlers
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect({ target: fileInput });
    }
});

function handleFileSelect(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileInfo = document.getElementById('fileInfo');
    
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    
    dropZone.style.display = 'none';
    fileInfo.style.display = 'block';
}

function clearFile() {
    fileInput.value = '';
    document.getElementById('fileInfo').style.display = 'none';
    dropZone.style.display = 'block';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
}

async function uploadSession(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = document.getElementById('submitBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    submitBtn.disabled = true;
    uploadProgress.style.display = 'block';
    
    try {
        const xhr = new XMLHttpRequest();
        
        // Upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
            }
        });
        
        // Complete
        xhr.addEventListener('load', function() {
            console.log('Upload response status:', xhr.status);
            console.log('Upload response:', xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        progressText.textContent = 'Upload complete! Redirecting...';
                        setTimeout(() => {
                            window.location.href = '/campaigns/<?= $campaign['id'] ?>';
                        }, 1500);
                    } else {
                        alert('Error: ' + (response.error || 'Upload failed'));
                        submitBtn.disabled = false;
                        uploadProgress.style.display = 'none';
                    }
                } catch (e) {
                    console.error('Failed to parse response:', e);
                    alert('Upload succeeded but response was invalid. Check console for details.');
                    submitBtn.disabled = false;
                    uploadProgress.style.display = 'none';
                }
            } else {
                // Try to parse error message from response
                let errorMsg = 'Upload failed with status ' + xhr.status;
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg = response.error;
                    }
                } catch (e) {
                    // Response is not JSON, use raw text if available
                    if (xhr.responseText) {
                        errorMsg += ': ' + xhr.responseText.substring(0, 200);
                    }
                }
                
                console.error('Upload failed:', errorMsg);
                alert('Upload Error: ' + errorMsg);
                submitBtn.disabled = false;
                uploadProgress.style.display = 'none';
            }
        });
        
        xhr.addEventListener('error', function() {
            alert('Upload failed. Please check your connection and try again.');
            submitBtn.disabled = false;
            uploadProgress.style.display = 'none';
        });
        
        xhr.open('POST', '/campaigns/<?= $campaign['id'] ?>/sessions');
        xhr.send(formData);
        
    } catch (error) {
        alert('Error: ' + error.message);
        submitBtn.disabled = false;
        uploadProgress.style.display = 'none';
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
