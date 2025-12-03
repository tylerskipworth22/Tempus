const input = document.getElementById("uploadedFiles");
const fileList = document.getElementById("fileList");
const form = document.getElementById("createCapsuleForm");

let selectedFiles = [];
let fileDescriptions = {};

//allowed types & max sizes in MB
const file_categories = {
    image: { ext: ['jpg','jpeg','png','gif','webp'], max: 25 },
    video: { ext: ['mp4','mov','avi','mkv'], max: 500 },
    audio: { ext: ['mp3','wav','m4a'], max: 50 },
    document: { ext: ['pdf','docx','txt'], max: 50 }
};

input.addEventListener("change", (e) => {
    Array.from(e.target.files).forEach(file => {
        const ext = file.name.split('.').pop().toLowerCase();

        //determine type
        let type = null;
        for (let key in file_categories) {
            if (file_categories[key].ext.includes(ext)) {
                type = key;
                break;
            }
        }

        if (!type) {
            alert(`File type not accepted: ${file.name}`);
            return;
        }

        const sizeMb = file.size / 1024 / 1024;
        if (sizeMb > file_categories[type].max) {
            alert(`File too big: ${file.name} (Max ${file_categories[type].max} MB)`);
            return;
        }

        if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            selectedFiles.push(file);
        }
    });

    updateFileList();
});

//update new file previews
function updateFileList() {
    fileList.innerHTML = "";
    fileDescriptions = {};

    if (selectedFiles.length === 0) {
        fileList.textContent = "No files selected.";
        return;
    }

    selectedFiles.forEach((file, index) => {
        fileDescriptions[file.name] = fileDescriptions[file.name] || "";

        const fileItem = document.createElement("div");
        fileItem.classList.add("file-item");

        // Preview container
        const preview = document.createElement("div");
        preview.classList.add("file-preview");

        const ext = file.name.split(".").pop().toLowerCase();

        if (file.type.startsWith("image/")) {
            const img = document.createElement("img");
            img.src = URL.createObjectURL(file);
            img.style.maxWidth = "400px";
            img.style.maxHeight = "300px";
            img.style.objectFit = "contain";
            img.style.marginBottom = "10px";
            preview.appendChild(img);
        } else if (file.type.startsWith("video/")) {
            const video = document.createElement("video");
            video.src = URL.createObjectURL(file);
            video.controls = true;
            video.style.maxWidth = "400px";
            video.style.maxHeight = "300px";
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
            iframe.style.height = "400px"; // tall enough to read
            iframe.style.border = "1px solid #ccc";
            preview.appendChild(iframe);
        } else if (ext === "docx") {
            const iframe = document.createElement("iframe");
            iframe.src = `https://docs.google.com/gview?url=${URL.createObjectURL(file)}&embedded=true`;
            iframe.style.width = "100%";
            iframe.style.height = "400px";
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
        } else {
            const placeholder = document.createElement("div");
            placeholder.textContent = "📄 " + file.name;
            preview.appendChild(placeholder);
        }

        //file info (name, description, remove button)
        const fileMeta = document.createElement("div");
        fileMeta.classList.add("file-meta");

        const label = document.createElement("p");
        label.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`;

        const descInput = document.createElement("input");
        descInput.type = "text";
        descInput.placeholder = "Add description...";
        descInput.value = fileDescriptions[file.name] || "";
        descInput.addEventListener("input", (e) => {
            fileDescriptions[file.name] = e.target.value;
        });

        const removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.textContent = "Remove";
        removeBtn.addEventListener("click", () => {
            selectedFiles.splice(index, 1);
            updateFileList();
        });

        fileMeta.appendChild(label);
        fileMeta.appendChild(descInput);
        fileMeta.appendChild(removeBtn);

        fileItem.appendChild(preview);
        fileItem.appendChild(fileMeta);
        fileList.appendChild(fileItem);
    });
}

form.addEventListener("submit", () => {
    form.querySelectorAll("input[name='fileDescriptions[]']").forEach(i => i.remove());

    selectedFiles.forEach((file) => {
        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = "fileDescriptions[]";
        hidden.value = fileDescriptions[file.name] || "";
        form.appendChild(hidden);
    });

    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    input.files = dt.files;
});
