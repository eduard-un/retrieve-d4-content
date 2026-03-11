(function () {
	var editorInstance = null;

	function downloadJson(filename, obj) {
		var json = JSON.stringify(obj);
		var blob = new Blob([json], { type: 'application/json;charset=utf-8' });
		var url = URL.createObjectURL(blob);
		var a = document.createElement('a');
		a.href = url;
		a.download = filename;
		a.style.display = 'none';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		setTimeout(function () {
			URL.revokeObjectURL(url);
		}, 1000);
	}

	function safeFilePart(value) {
		return String(value || '')
			.trim()
			.toLowerCase()
			.replace(/[^a-z0-9._-]+/g, '-')
			.replace(/-+/g, '-')
			.replace(/^-|-$/g, '');
	}

	function initCodeEditor() {
		if (!window.wp || !wp.codeEditor) return;
		if (!window.DT_D4_CODE_EDITOR_SETTINGS) return;

		var textarea = document.getElementById('dt_d4_content');
		if (!textarea) return;

		try {
			var result = wp.codeEditor.initialize(textarea, window.DT_D4_CODE_EDITOR_SETTINGS);
			if (result && result.codemirror) {
				editorInstance = result.codemirror;
				editorInstance.setOption('readOnly', 'nocursor');
				editorInstance.refresh();

				// Delayed refresh so content renders when the container
				// is inside a metabox that may not be fully laid out yet.
				setTimeout(function () {
					editorInstance.refresh();
				}, 100);
			}
		} catch (e) {
			// If CodeMirror fails for any reason, fallback to plain textarea.
		}
	}

	function selectAllFallbackTextarea() {
		var textarea = document.getElementById('dt_d4_content');
		if (!textarea) return;
		textarea.focus();
		textarea.select();
	}

	function copyText(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}

		return new Promise(function (resolve, reject) {
			try {
				var temp = document.createElement('textarea');
				temp.value = text;
				temp.setAttribute('readonly', 'readonly');
				temp.style.position = 'absolute';
				temp.style.left = '-9999px';
				document.body.appendChild(temp);
				temp.select();
				var ok = document.execCommand('copy');
				document.body.removeChild(temp);
				ok ? resolve() : reject(new Error('Copy failed'));
			} catch (e) {
				reject(e);
			}
		});
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.dt-d4-save-json');
		if (!btn) return;

		var targetId = btn.getAttribute('data-source-target') || 'dt_d4_content';
		var textarea = document.getElementById(targetId);
		if (!textarea) return;

		var postId = btn.getAttribute('data-post-id') || '';
		var metaValue = editorInstance ? editorInstance.getValue() : textarea.value;
		var postIdInt = postId ? parseInt(postId, 10) : NaN;
		if (!postId || Number.isNaN(postIdInt) || postIdInt <= 0) {
			postIdInt = null;
		}

		var payload = {
			context: 'et_builder',
			data: {},
			presets: {},
			global_colors: [],
			images: [],
			thumbnails: [],
		};

		if (postIdInt) {
			payload.data[String(postIdInt)] = metaValue;
		} else {
			payload.data.unknown = metaValue;
		}

		var fileName = 'divi4-layout';
		if (postId) fileName += '-' + safeFilePart(postId);
		fileName += '.json';

		downloadJson(fileName, payload);
	});

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.dt-d4-copy');
		if (!btn) return;

		var targetId = btn.getAttribute('data-copy-target');
		if (!targetId) return;

		var textarea = document.getElementById(targetId);
		if (!textarea) return;

		var textToCopy = editorInstance ? editorInstance.getValue() : textarea.value;

		var original = btn.textContent;
		btn.disabled = true;

		copyText(textToCopy)
			.then(function () {
				btn.textContent = 'Copied';
				setTimeout(function () {
					btn.textContent = original;
					btn.disabled = false;
				}, 1200);
			})
			.catch(function () {
				btn.textContent = 'Copy failed';
				setTimeout(function () {
					btn.textContent = original;
					btn.disabled = false;
				}, 1400);
			});
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initCodeEditor();
			if (!editorInstance) {
				selectAllFallbackTextarea();
			}
		});
	} else {
		initCodeEditor();
		if (!editorInstance) {
			selectAllFallbackTextarea();
		}
	}
})();
