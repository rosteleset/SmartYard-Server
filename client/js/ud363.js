function uploadAllFiles(fileList) {
    const files = Array.from(fileList);

    // Функция для загрузки ОДНОГО файла целиком
    function uploadSingleFile(file) {
        const CHUNK_SIZE = 1024 * 1024;

        function uploadNextChunk(offset) {
            if (offset >= file.size) return Promise.resolve();

            const formData = new FormData();
            formData.append('file_chunk', file.slice(offset, offset + CHUNK_SIZE));
            formData.append('file_name', file.name);

            return fetch('upload.php', { method: 'POST', body: formData })
                .then(() => {
                    console.log(`Файл ${file.name}: ${Math.min(100, Math.round((offset + CHUNK_SIZE) / file.size * 100))}%`);
                    return uploadNextChunk(offset + CHUNK_SIZE);
                });
        }

        console.log(`Начало загрузки файла: ${file.name}`);
        return uploadNextChunk(0);
    }

    // Последовательный запуск файлов через reduce
    return files.reduce((promiseChain, currentFile) => {
        return promiseChain.then(() => uploadSingleFile(currentFile));
    }, Promise.resolve());
}

// Использование:
const fileInput = document.querySelector('input[type="file"]');
uploadAllFiles(fileInput.files).then(() => alert('Все файлы загружены!'));
