// edit_preview.js
const newFilesInput = document.getElementById("uploadedFiles");
const newFileList = document.getElementById("fileList");
const existingFilesContainer = document.getElementById("existingFiles");
const filesToRemoveInput = document.getElementById("filesToRemove");
const editForm = document.getElementById("editCapsuleForm");

let newFiles = [];
let fileDescriptions = {};
let filesToRemove = [];

// ----------------------
// Handle new file selection
// ----------------------
if (newFilesInput) {
    newFilesInput.addEventListener("change", (e) => {
        Array.from(e.target.files).forEach(file => {
            if (!newFiles.some(f => f.name === file.name && f.size === file.size)) {
                newFiles.push(file);
            }
        });
        updateNewFileList();
    });
}

function updateNewFileList() {
    newFileList.innerHTML = "";
    fileDescriptions = {};

    if (newFiles.length === 0) {
        newFileList.textContent = "No new files selected.";
        return;
    }

    newFiles.forEach((file, index) => {
        fileDescriptions[file.name] = fileDescriptions[file.name] || "";

        const fileItem = document.createElement("div");
        fileItem.classList.add("file-item");

        // Preview
        const preview = document.createElement("div");
        preview.classList.add("file-preview");

        const ext = file.name.split(".").pop().toLowerCase();

        if (file.type.startsWith("image/")) {
            const img = document.createElement("img");
            img.src = URL.createObjectURL(file);
            img.style.width = "80px";
            img.style.height = "80px";
            img.style.objectFit = "cover";
            preview.appendChild(img);
        } else if (file.type.startsWith("video/")) {
            const video = document.createElement("video");
            video.src = URL.createObjectURL(file);
            video.controls = true;
            video.style.width = "80px";
            video.style.height = "80px";
            preview.appendChild(video);
        } else if (file.type.startsWith("audio/")) {
            const audio = document.createElement("audio");
            audio.src = URL.createObjectURL(file);
            audio.controls = true;
            preview.appendChild(audio);
        } else {
            const placeholder = document.createElement("div");
            placeholder.textContent = "📄 " + file.name;
            preview.appendChild(placeholder);
        }

        const fileInfo = document.createElement("div");
        fileInfo.classList.add("file-info");

        const label = document.createElement("p");
        label.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`;

        const descInput = document.createElement("input");
        descInput.type = "text";
        descInput.placeholder = "Add description...";
        descInput.classList.add("file-description");
        descInput.value = fileDescriptions[file.name] || "";
        descInput.addEventListener("input", (e) => {
            fileDescriptions[file.name] = e.target.value;
        });

        const removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.textContent = "Remove";
        removeBtn.addEventListener("click", () => {
            newFiles.splice(index, 1);
            updateNewFileList();
        });

        fileInfo.appendChild(label);
        fileInfo.appendChild(descInput);
        fileInfo.appendChild(removeBtn);

        fileItem.appendChild(preview);
        fileItem.appendChild(fileInfo);
        newFileList.appendChild(fileItem);
    });
}

// ----------------------
// Handle existing files removal
// ----------------------
if (existingFilesContainer) {
    existingFilesContainer.addEventListener("click", (e) => {
        if (e.target.classList.contains("remove-existing-btn")) {
            const fileItem = e.target.closest(".file-item");
            const mediaId = fileItem.getAttribute("data-id");
            filesToRemove.push(mediaId);
            filesToRemoveInput.value = filesToRemove.join(",");
            fileItem.remove();
        }
    });
}

// ----------------------
// Handle form submission
// ----------------------
if (editForm) {
    editForm.addEventListener("submit", (e) => {
        // Attach descriptions for new files
        editForm.querySelectorAll("input[name='fileDescriptions[]']").forEach(i => i.remove());
        newFiles.forEach((file) => {
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "fileDescriptions[]";
            hidden.value = fileDescriptions[file.name] || "";
            editForm.appendChild(hidden);
        });

        // Ensure file input contains only newFiles
        const dt = new DataTransfer();
        newFiles.forEach(file => dt.items.add(file));
        newFilesInput.files = dt.files;
    });
}
