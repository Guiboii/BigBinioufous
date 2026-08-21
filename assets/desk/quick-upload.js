// Drag-and-drop upload: a dropped file (or whole folder) becomes a Document directly, keeping its folder path, which the server rebuilds as a matching Folder tree.
var zone = document.getElementById('quick-upload-zone');

if (zone) {
  var input = document.getElementById('quick-upload-input');
  var progress = document.getElementById('quick-upload-progress');
  var progressBar = document.getElementById('quick-upload-progress-bar');
  var progressTrack = document.getElementById('quick-upload-progress-track');
  var progressLabel = document.getElementById('quick-upload-progress-label');
  var status = document.getElementById('quick-upload-status');
  var errorMessages = {};
  try {
    errorMessages = JSON.parse(zone.dataset.errorMessages || '{}');
  } catch {
    errorMessages = {};
  }

  zone.addEventListener('click', function () {
    if (!zone.classList.contains('uploading')) {
      input.click();
    }
  });

  zone.addEventListener('keydown', function (e) {
    if ((e.key === 'Enter' || e.key === ' ') && !zone.classList.contains('uploading')) {
      e.preventDefault();
      input.click();
    }
  });

  zone.addEventListener('dragover', function (e) {
    e.preventDefault();
    if (!zone.classList.contains('uploading')) {
      zone.classList.add('dragover');
    }
  });

  zone.addEventListener('dragleave', function () {
    zone.classList.remove('dragover');
  });

  zone.addEventListener('drop', function (e) {
    e.preventDefault();
    zone.classList.remove('dragover');

    if (zone.classList.contains('uploading')) {
      return;
    }

    // A dropped folder doesn't appear in e.dataTransfer.files: the browser won't flatten it on its own, so DataTransferItem.webkitGetAsEntry() (despite the prefix, supported everywhere) is needed to walk subfolders recursively.
    var items = e.dataTransfer.items;
    if (items && items.length && items[0].webkitGetAsEntry) {
      var entries = [];
      for (var i = 0; i < items.length; i++) {
        var entry = items[i].webkitGetAsEntry();
        if (entry) {
          entries.push(entry);
        }
      }
      readEntries(entries).then(handleFiles);
    } else {
      handleFiles(Array.from(e.dataTransfer.files).map(withRootPath));
    }
  });

  input.addEventListener('change', function () {
    handleFiles(Array.from(input.files).map(withRootPath));
  });

  // When already inside a folder, everything dropped here must land inside it, in addition to its own subpath if any.
  function withRootPath(file) {
    return { file: file, path: zone.dataset.currentPath || '' };
  }

  // entry.fullPath looks like "/Set/02 Medley XXI/title.mp3": strip the filename and leading slash to keep just the folder path, prefixed with the current folder if any.
  function directoryOf(fullPath) {
    var withoutLeadingSlash = fullPath.replace(/^\//, '');
    var lastSlash = withoutLeadingSlash.lastIndexOf('/');
    var relative = lastSlash === -1 ? '' : withoutLeadingSlash.slice(0, lastSlash);
    var current = zone.dataset.currentPath || '';

    if (current && relative) {
      return current + '/' + relative;
    }

    return current || relative;
  }

  function readEntries(queue) {
    var result = [];

    function next() {
      if (queue.length === 0) {
        return Promise.resolve(result);
      }
      var entry = queue.shift();
      if (entry.isFile) {
        return new Promise(function (resolve, reject) {
          entry.file(resolve, reject);
        })
          .then(function (file) {
            result.push({ file: file, path: directoryOf(entry.fullPath) });
          })
          .then(next);
      }
      if (entry.isDirectory) {
        return readDirectory(entry.createReader()).then(function (childEntries) {
          queue = queue.concat(childEntries);

          return next();
        });
      }

      return next();
    }

    return next();
  }

  function readDirectory(reader) {
    var entries = [];

    function readBatch() {
      return new Promise(function (resolve, reject) {
        reader.readEntries(resolve, reject);
      }).then(function (batch) {
        if (batch.length === 0) {
          return entries;
        }
        entries = entries.concat(batch);

        return readBatch();
      });
    }

    return readBatch();
  }

  // Uploaded one at a time rather than in parallel: gives an accurate progress bar and avoids two files of the same new subfolder racing to create it at once.
  function handleFiles(entries) {
    if (entries.length === 0) {
      return;
    }

    zone.classList.add('uploading');
    zone.setAttribute('aria-disabled', 'true');
    input.disabled = true;
    status.textContent = '';
    progress.hidden = false;
    updateProgress(0, entries.length, entries[0].file.name);

    var failed = [];

    function next(index) {
      if (index >= entries.length) {
        return Promise.resolve();
      }

      return uploadEntry(entries[index]).then(function (result) {
        if (!result.ok) {
          failed.push({ name: entries[index].file.name, error: result.error });
        }
        updateProgress(
          index + 1,
          entries.length,
          entries[index + 1] ? entries[index + 1].file.name : '',
        );

        return next(index + 1);
      });
    }

    next(0).then(function () {
      if (failed.length === 0) {
        window.location.reload();

        return;
      }

      zone.classList.remove('uploading');
      zone.removeAttribute('aria-disabled');
      input.disabled = false;
      progress.hidden = true;
      failed.forEach(function (failure) {
        var item = document.createElement('li');
        var message = errorMessages[failure.error] || zone.dataset.error;
        item.textContent = failure.name + ' : ' + message;
        status.appendChild(item);
      });
    });
  }

  function updateProgress(done, total, nextFileName) {
    var percent = Math.round((done / total) * 100);
    progressBar.style.width = percent + '%';
    progressTrack.setAttribute('aria-valuenow', String(percent));
    progressLabel.textContent =
      done +
      ' / ' +
      total +
      (nextFileName ? ' — ' + zone.dataset.uploading + ' ' + nextFileName : '');
  }

  function uploadEntry(entry) {
    var formData = new FormData();
    formData.append('file', entry.file);
    formData.append('path', entry.path);
    formData.append('_token', zone.dataset.token);

    return fetch(zone.dataset.documentAction, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok && data.success === true, error: data.error || null };
        });
      })
      .catch(function () {
        return { ok: false, error: null };
      });
  }
}
