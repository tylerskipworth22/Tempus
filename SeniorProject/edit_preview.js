const newFilesInput = document.getElementById("uploadedFiles");
const newFileList = document.getElementById("fileList");
const existingFilesContainer = document.getElementById("existingFiles");
const filesToRemoveInput = document.getElementById("filesToRemove");
const editForm = document.getElementById("editCapsuleForm");

let newFiles = [];
let fileDescriptions = {};
let filesToRemove = [];

//allowed file categories & max sizes (MB)
const fileCategories = {
    image: { ext: ['jpg','jpeg','png','gif','webp'], max: 25 },
    video: { ext: ['mp4','mov','avi','mkv'], max: 500 },
    audio: { ext: ['mp3','wav','m4a'], max: 50 },
    document: { ext: ['pdf','docx','txt'], max: 50 }
};

//handle new file selection
if (newFilesInput) {
    newFilesInput.addEventListener("change", (e) => {
        Array.from(e.target.files).forEach(file => {
            const ext = file.name.split('.').pop().toLowerCase();

            //determine file type
            let type = null;
            for (let key in fileCategories) {
                if (fileCategories[key].ext.includes(ext)) {
                    type = key;
                    break;
                }
            }

            if (!type) {
                alert(`File type not accepted: ${file.name}`);
                return;
            }

            const sizeMb = file.size / 1024 / 1024;
            if (sizeMb > fileCategories[type].max) {
                alert(`File too big: ${file.name} (Max ${fileCategories[type].max} MB)`);
                return;
            }

            //add file if not already added
            if (!newFiles.some(f => f.name === file.name && f.size === file.size)) {
                newFiles.push(file);
            }
        });
        updateNewFileList();
    });
}

//update new file previews
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

        const preview = document.createElement("div");
        preview.classList.add("file-preview");

        const ext = file.name.split(".").pop().toLowerCase();

        if (file.type.startsWith("image/")) {
            const img = document.createElement("img");
            img.src = URL.createObjectURL(file);
            img.style.width = "100%";
            img.style.height = "auto";
            img.style.marginBottom = "10px";
            preview.appendChild(img);
        } else if (file.type.startsWith("video/")) {
            const video = document.createElement("video");
            video.src = URL.createObjectURL(file);
            video.controls = true;
            video.style.width = "400px";
            video.style.height = "300px";
            preview.appendChild(video);
        } else if (file.type.startsWith("audio/")) {
            const audio = document.createElement("audio");
            audio.src = URL.createObjectURL(file);
            audio.controls = true;
            preview.appendChild(audio);
        } else if (ext === "pdf") {
            const iframe = document.createElement("iframe");
            iframe.src = URL.createObjectURL(file);
            iframe.style.width = "100%";
            iframe.style.height = "200px";
            iframe.style.border = "1px solid #ccc";
            preview.appendChild(iframe);
        } else if (ext === "txt") {
            const reader = new FileReader();
            reader.onload = (e) => {
                const pre = document.createElement("pre");
                pre.textContent = e.target.result.substring(0, 500);
                pre.classList.add("preview-text");
                preview.appendChild(pre);
            };
            reader.readAsText(file);
        } else if (ext === "docx") {
            const iframe = document.createElement("iframe");
            iframe.src = `https://docs.google.com/gview?url=${URL.createObjectURL(file)}&embedded=true`;
            iframe.style.width = "100%";
            iframe.style.height = "200px";
            iframe.style.border = "1px solid #ccc";
            preview.appendChild(iframe);
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

//handle existing files removal
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

//handle form submission
if (editForm) {
    editForm.addEventListener("submit", (e) => {
        //remove old hidden inputs
        editForm.querySelectorAll("input[name='newFileDescriptions[]']").forEach(i => i.remove());

        //add hidden inputs for new file descriptions
        newFiles.forEach((file) => {
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "newFileDescriptions[]";
            hidden.value = fileDescriptions[file.name] || "";
            editForm.appendChild(hidden);
        });

        //ensure file input contains only newFiles
        const dt = new DataTransfer();
        newFiles.forEach(file => dt.items.add(file));
        newFilesInput.files = dt.files;
    });
}
