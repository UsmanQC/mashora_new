@once
    @push('scripts')
        <script>
            window.MashoraAgoraMediaControls = (function () {
                function syncButton(btn, isOff) {
                    if (!btn) {
                        return;
                    }

                    btn.classList.toggle('video-call-control--off', isOff);
                    btn.setAttribute('aria-pressed', isOff ? 'true' : 'false');
                    btn.title = isOff ? (btn.dataset.labelOff || '') : (btn.dataset.labelOn || '');
                    btn.disabled = false;

                    const label = btn.querySelector('[data-control-label]');
                    if (label) {
                        label.textContent = isOff
                            ? (btn.dataset.labelOff || '')
                            : (btn.dataset.labelOn || '');
                    }
                }

                function showControlsForMode(micBtnId, videoBtnId, mode) {
                    const micBtn = document.getElementById(micBtnId);
                    const videoBtn = document.getElementById(videoBtnId);

                    [micBtn, videoBtn].forEach((btn) => {
                        if (!btn) {
                            return;
                        }

                        btn.classList.remove('hidden');

                        if (btn.classList.contains('doctor-consultation-call-controls__btn')) {
                            btn.classList.add('inline-flex');
                        }
                    });

                    if (videoBtn) {
                        const hideVideo = mode !== 'video';
                        videoBtn.classList.toggle('hidden', hideVideo);

                        if (hideVideo && videoBtn.classList.contains('doctor-consultation-call-controls__btn')) {
                            videoBtn.classList.remove('inline-flex');
                        }
                    }
                }

                function sync(options) {
                    const micBtn = document.getElementById(options.micBtnId);
                    const videoBtn = document.getElementById(options.videoBtnId);
                    const localAudio = options.localAudio || null;
                    const localVideo = options.localVideo || null;
                    const hasMic = Boolean(localAudio);
                    const hasVideo = Boolean(localVideo);

                    if (micBtn) {
                        micBtn.disabled = !hasMic;
                        syncButton(micBtn, hasMic && !localAudio.enabled);
                    }

                    if (videoBtn && !videoBtn.classList.contains('hidden')) {
                        videoBtn.disabled = !hasVideo;
                        syncButton(videoBtn, hasVideo && !localVideo.enabled);
                    }

                    const localPreview = options.localPreviewId
                        ? document.getElementById(options.localPreviewId)?.closest('.video-call-local-preview, .absolute')
                        : null;

                    if (localPreview) {
                        const cameraOff = hasVideo && !localVideo.enabled;
                        localPreview.classList.toggle('opacity-40', cameraOff);
                        localPreview.classList.toggle('grayscale', cameraOff);
                    }
                }

                async function toggleMic(options) {
                    const track = options.localAudio;
                    if (!track) {
                        return false;
                    }

                    await track.setEnabled(!track.enabled);
                    sync(options);

                    return true;
                }

                async function toggleVideo(options) {
                    const track = options.localVideo;
                    if (!track) {
                        return false;
                    }

                    await track.setEnabled(!track.enabled);
                    sync(options);

                    return true;
                }

                return {
                    sync,
                    toggleMic,
                    toggleVideo,
                    showControlsForMode,
                };
            })();
        </script>
    @endpush
@endonce
