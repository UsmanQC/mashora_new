export function initPatientSwipeBack() {
    document
        .querySelectorAll('#patient-auth-swipe-surface, #patient-portal-swipe-surface, [data-swipe-livewire-method]')
        .forEach((surface) => bindPatientSwipeSurface(surface));
}

function bindPatientSwipeSurface(surface) {
    if (!(surface instanceof HTMLElement) || surface.dataset.swipeBound === '1') {
        return;
    }

    const backUrl = surface.dataset.backUrl ?? '';
    const livewireMethod = surface.dataset.swipeLivewireMethod ?? '';

    if (backUrl === '' && livewireMethod === '') {
        return;
    }

    surface.dataset.swipeBound = '1';

    const hintId = surface.dataset.swipeHintId ?? surface.id.replace('-surface', '-hint');
    const hint = hintId ? document.getElementById(hintId) : null;
    const edgeWidth = 28;
    const triggerDistance = 80;
    const maxDrag = Math.min(window.innerWidth * 0.42, 168);

    let startX = 0;
    let startY = 0;
    let tracking = false;
    let dragging = false;

    const isRtl = () => document.documentElement.dir === 'rtl';

    const edgeDistance = (clientX) => {
        return isRtl() ? window.innerWidth - clientX : clientX;
    };

    const dragDelta = (clientX) => {
        const delta = clientX - startX;

        return isRtl() ? -delta : delta;
    };

    const resetGesture = () => {
        tracking = false;
        dragging = false;
        surface.classList.remove('patient-auth-swipe-active');
        surface.style.transform = '';
        surface.style.transition = '';

        if (hint instanceof HTMLElement) {
            hint.classList.remove('patient-auth-swipe-hint-visible');
            hint.style.opacity = '';
        }
    };

    const callLivewireBack = () => {
        const wireRoot = surface.closest('[wire\\:id]');
        const wireId = wireRoot?.getAttribute('wire:id');

        if (!wireId || !window.Livewire?.find) {
            return false;
        }

        const component = window.Livewire.find(wireId);

        if (!component) {
            return false;
        }

        component.call(livewireMethod);

        return true;
    };

    const navigateBack = () => {
        if (! usesLivewireNavigate(backUrl)) {
            window.location.assign(backUrl);

            return;
        }

        if (window.Livewire?.navigate) {
            window.Livewire.navigate(backUrl);

            return;
        }

        window.location.assign(backUrl);
    };

    const applyDrag = (distance) => {
        const clamped = Math.max(0, Math.min(distance, maxDrag));
        const progress = clamped / triggerDistance;
        const offset = isRtl() ? -clamped : clamped;

        surface.style.transform = `translate3d(${offset}px, 0, 0)`;

        if (hint instanceof HTMLElement) {
            hint.classList.add('patient-auth-swipe-hint-visible');
            hint.style.opacity = String(Math.min(1, progress));
        }
    };

    surface.addEventListener(
        'touchstart',
        (event) => {
            if (event.touches.length !== 1 || surface.dataset.swipeAnimating === '1') {
                return;
            }

            const touch = event.touches[0];

            if (edgeDistance(touch.clientX) > edgeWidth) {
                return;
            }

            startX = touch.clientX;
            startY = touch.clientY;
            tracking = true;
            dragging = false;
            surface.style.transition = '';
        },
        { passive: true },
    );

    surface.addEventListener(
        'touchmove',
        (event) => {
            if (!tracking || event.touches.length !== 1) {
                return;
            }

            const touch = event.touches[0];
            const deltaX = dragDelta(touch.clientX);
            const deltaY = Math.abs(touch.clientY - startY);

            if (!dragging) {
                if (deltaY > 16 && deltaY > Math.abs(deltaX)) {
                    tracking = false;

                    return;
                }

                if (deltaX <= 8) {
                    return;
                }

                dragging = true;
                surface.classList.add('patient-auth-swipe-active');
            }

            if (deltaX <= 0) {
                resetGesture();

                return;
            }

            event.preventDefault();
            applyDrag(deltaX);
        },
        { passive: false },
    );

    const finishGesture = (event) => {
        if (!tracking) {
            return;
        }

        const touch = event.changedTouches[0];
        const deltaX = dragging ? dragDelta(touch.clientX) : 0;

        tracking = false;

        if (!dragging || deltaX < triggerDistance) {
            surface.style.transition = 'transform 180ms ease';
            resetGesture();

            return;
        }

        dragging = false;

        if (livewireMethod !== '') {
            resetGesture();
            callLivewireBack();

            return;
        }

        surface.dataset.swipeAnimating = '1';
        surface.style.transition = 'transform 220ms ease';
        const exitOffset = isRtl() ? -window.innerWidth : window.innerWidth;
        surface.style.transform = `translate3d(${exitOffset}px, 0, 0)`;

        window.setTimeout(() => {
            surface.dataset.swipeAnimating = '0';
            navigateBack();
        }, 220);
    };

    surface.addEventListener('touchend', finishGesture, { passive: true });
    surface.addEventListener('touchcancel', resetGesture, { passive: true });
}

if (document.querySelector('#patient-auth-swipe-surface, #patient-portal-swipe-surface, [data-swipe-livewire-method]')) {
    initPatientSwipeBack();
}

document.addEventListener('DOMContentLoaded', initPatientSwipeBack);
document.addEventListener('livewire:navigated', initPatientSwipeBack);

function usesLivewireNavigate(url) {
    try {
        const path = new URL(url, window.location.origin).pathname;

        return path !== '/' && path.startsWith('/patient');
    } catch {
        return false;
    }
}
