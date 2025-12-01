const input = document.getElementById("uploadedFiles");
const fileList = document.getElementById("fileList");
const form = document.getElementById("createCapsuleForm");

let selectedFiles = [];
let fileDescriptions = {};

input.addEventListener("change", (e) => {
    Array.from(e.target.files).forEach(file => {
        if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            selectedFiles.push(file);
        }
    });
    updateFileList();
});

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

        // Preview
        const preview = document.createElement("div");
        preview.classList.add("file-preview");

        const ext = file.name.split(".").pop().toLowerCase();

        if (file.type.startsWith("image/")) {
            const img = document.createElement("img");
            img.src = URL.createObjectURL(file);
            preview.appendChild(img);
        } else if (file.type.startsWith("video/")) {
            const video = document.createElement("video");
            video.src = URL.createObjectURL(file);
            video.controls = true;
            preview.appendChild(video);
        } else if (file.type.startsWith("audio/")) {
            const audioContainer = document.createElement("div");
            audioContainer.classList.add("audio-preview");

            const audio = document.createElement("audio");
            audio.src = URL.createObjectURL(file);
            audio.controls = true;

            const filename = document.createElement("span");
            filename.textContent = file.name;
            filename.classList.add("filename");

            audioContainer.appendChild(audio);
            audioContainer.appendChild(filename);
            preview.appendChild(audioContainer);
        } else if (ext === "pdf") {
            const iframe = document.createElement("iframe");
            iframe.src = URL.createObjectURL(file);
            iframe.classList.add("preview-pdf");
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

        // File info container (description + remove)
        const fileMeta = document.createElement("div");
        fileMeta.classList.add("file-meta");

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

form.addEventListener("submit", (e) => {
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
