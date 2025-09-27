const MAX_FILES = 5;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

const formatFileSize = (bytes) => {
    if (!bytes) {
        return '0KB';
    }

    if (bytes >= 1048576) {
        return `${(bytes / 1048576).toFixed(1)}MB`;
    }

    return `${Math.round(bytes / 1024)}KB`;
};

const buildPendingCard = (file, index) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm';
    wrapper.setAttribute('data-skill-pending-item', '');
    wrapper.dataset.index = String(index);

    const header = document.createElement('div');
    header.className = 'flex items-center justify-between gap-3 text-sm text-slate-700';

    const name = document.createElement('span');
    name.className = 'truncate';
    name.title = file.name;
    name.textContent = file.name;

    const meta = document.createElement('span');
    meta.className = 'shrink-0 text-xs text-slate-400';
    meta.textContent = `${formatFileSize(file.size)} · 選択済み`;

    header.appendChild(name);
    header.appendChild(meta);

    const progressBar = document.createElement('div');
    progressBar.className = 'mt-3 h-2 rounded-full bg-slate-200';
    progressBar.setAttribute('aria-hidden', 'true');

    const progress = document.createElement('div');
    progress.className = 'h-2 rounded-full bg-amber-400';
    progress.style.width = '40%';
    progressBar.appendChild(progress);

    const footer = document.createElement('div');
    footer.className = 'mt-3 flex items-center justify-between text-xs text-slate-500';

    const status = document.createElement('span');
    status.className = 'inline-flex items-center gap-1 font-semibold text-amber-500';
    status.innerHTML = '<span class="inline-block h-2 w-2 rounded-full bg-amber-400"></span>アップロード待機中';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'rounded-full border border-slate-300 px-2 py-1 text-[11px] font-semibold text-slate-500 transition hover:bg-slate-100';
    removeButton.textContent = '取り消す';
    removeButton.setAttribute('data-skill-remove', String(index));

    footer.appendChild(status);
    footer.appendChild(removeButton);

    wrapper.appendChild(header);
    wrapper.appendChild(progressBar);
    wrapper.appendChild(footer);

    return wrapper;
};

const isPdfFile = (file) => {
    if (!file) {
        return false;
    }

    if (file.type === 'application/pdf') {
        return true;
    }

    return file.name?.toLowerCase().endsWith('.pdf');
};

const initialiseSkillSheetUploader = () => {
    const dropzone = document.querySelector('[data-skill-dropzone]');
    const fileInput = document.querySelector('[data-skill-input]');
    const pendingSection = document.querySelector('[data-skill-pending-section]');
    const pendingList = document.querySelector('[data-skill-pending-list]');
    const feedback = document.querySelector('[data-skill-feedback]');
    const emptyPlaceholder = document.querySelector('[data-skill-empty-placeholder]');
    const clearButton = document.querySelector('[data-skill-clear]');

    if (!dropzone || !fileInput || !pendingSection || !pendingList) {
        return;
    }

    const existingCount = Number(dropzone.dataset.skillExistingCount || '0');
    let selectedFiles = [];

    const setFeedback = (message, type = 'info') => {
        if (!feedback) {
            return;
        }

        feedback.textContent = message || '';

        if (!message) {
            feedback.classList.add('hidden');
            feedback.classList.remove('text-red-600', 'text-blue-600', 'text-green-600');
            return;
        }

        feedback.classList.remove('hidden', 'text-red-600', 'text-blue-600', 'text-green-600');

        const className = type === 'error' ? 'text-red-600' : type === 'success' ? 'text-green-600' : 'text-blue-600';
        feedback.classList.add(className);
    };

    const syncInputFiles = () => {
        if (typeof DataTransfer === 'undefined') {
            setFeedback('ご利用のブラウザではファイルのドラッグ＆ドロップに対応していません。', 'error');
            return;
        }

        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(({ file }) => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    };

    const renderPendingFiles = () => {
        pendingList.innerHTML = '';

        if (selectedFiles.length === 0) {
            pendingSection.classList.add('hidden');
        } else {
            pendingSection.classList.remove('hidden');
            selectedFiles.forEach(({ file }, index) => {
                const card = buildPendingCard(file, index);
                pendingList.appendChild(card);
            });
        }

        if (emptyPlaceholder) {
            emptyPlaceholder.classList.toggle('hidden', selectedFiles.length > 0);
        }
    };

    const removeByIndex = (index) => {
        selectedFiles.splice(index, 1);
        syncInputFiles();
        renderPendingFiles();
        if (selectedFiles.length === 0) {
            setFeedback('選択したファイルをすべて取り消しました。', 'info');
        } else {
            setFeedback('1件のファイルを取り消しました。', 'info');
        }
    };

    const updateRemainingCapacity = () => MAX_FILES - existingCount - selectedFiles.length;

    const handleNewFiles = (fileList) => {
        const files = Array.from(fileList || []);
        if (files.length === 0) {
            return;
        }

        let added = 0;
        let invalidType = 0;
        let oversize = 0;
        let duplicates = 0;
        let capacityReached = false;

        const remainingBefore = updateRemainingCapacity();
        if (remainingBefore <= 0) {
            setFeedback('既存のファイルを含めて最大5件までです。これ以上追加できません。', 'error');
            return;
        }

        files.forEach((file) => {
            if (!isPdfFile(file)) {
                invalidType += 1;
                return;
            }

            if (file.size > MAX_FILE_SIZE) {
                oversize += 1;
                return;
            }

            const key = `${file.name}-${file.size}-${file.lastModified}`;
            const alreadySelected = selectedFiles.some((item) => item.key === key);
            if (alreadySelected) {
                duplicates += 1;
                return;
            }

            if (updateRemainingCapacity() <= 0) {
                capacityReached = true;
                return;
            }

            selectedFiles.push({ file, key });
            added += 1;
        });

        if (added > 0) {
            syncInputFiles();
            renderPendingFiles();
        }

        const messages = [];
        let messageType = 'info';

        if (added > 0) {
            messages.push(`${added}件のファイルを追加しました。`);
            messageType = invalidType === 0 && oversize === 0 && duplicates === 0 && !capacityReached ? 'success' : 'info';
        }

        if (invalidType > 0) {
            messages.push(`${invalidType}件のファイルは PDF 形式ではありません。`);
        }

        if (oversize > 0) {
            messages.push(`${oversize}件のファイルが 10MB を超えています。`);
        }

        if (duplicates > 0) {
            messages.push('同じファイルが既に選択されています。');
        }

        if (capacityReached || updateRemainingCapacity() <= 0) {
            messages.push('これ以上ファイルを追加できません。');
        }

        if (messages.length > 0) {
            setFeedback(messages.join(' '), messageType);
        } else {
            setFeedback('');
        }
    };

    const highlightDropzone = () => {
        dropzone.classList.add('border-blue-400', 'bg-blue-50');
    };

    const resetDropzoneHighlight = () => {
        dropzone.classList.remove('border-blue-400', 'bg-blue-50');
    };

    dropzone.addEventListener('dragenter', (event) => {
        event.preventDefault();
        highlightDropzone();
    });

    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        highlightDropzone();
    });

    dropzone.addEventListener('dragleave', (event) => {
        if (!dropzone.contains(event.relatedTarget)) {
            resetDropzoneHighlight();
        }
    });

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        resetDropzoneHighlight();
        handleNewFiles(event.dataTransfer?.files);
    });

    fileInput.addEventListener('change', (event) => {
        handleNewFiles(event.target.files);
        fileInput.value = '';
        syncInputFiles();
    });

    pendingList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-skill-remove]');
        if (!button) {
            return;
        }

        const index = Number(button.getAttribute('data-skill-remove'));
        if (!Number.isNaN(index)) {
            removeByIndex(index);
        }
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            if (selectedFiles.length === 0) {
                setFeedback('取り消すファイルはありません。', 'info');
                return;
            }

            selectedFiles = [];
            syncInputFiles();
            renderPendingFiles();
            setFeedback('選択したファイルをすべて取り消しました。', 'info');
        });
    }

    renderPendingFiles();
};

document.addEventListener('DOMContentLoaded', initialiseSkillSheetUploader);
