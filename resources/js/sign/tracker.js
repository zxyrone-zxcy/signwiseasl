import { FilesetResolver, HandLandmarker } from '@mediapipe/tasks-vision';

// MediaPipe files are served from /public/mediapipe (see setup steps), so nothing is loaded from a CDN.
const WASM_PATH = '/mediapipe/wasm';
const MODEL_PATH = '/mediapipe/hand_landmarker.task';

export async function createTracker() {
  const vision = await FilesetResolver.forVisionTasks(WASM_PATH);
  const landmarker = await HandLandmarker.createFromOptions(vision, {
    baseOptions: { modelAssetPath: MODEL_PATH },
    runningMode: 'VIDEO',
    numHands: 2,
  });

  return {
    /** Returns an array of 0..2 hands; each hand is an array of 21 {x, y, z} landmarks. */
    detect(video, timestampMs) {
      return landmarker.detectForVideo(video, timestampMs).landmarks ?? [];
    },
    close() {
      landmarker.close();
    },
  };
}

export function startCamera() {
  if (!navigator.mediaDevices?.getUserMedia) {
    return Promise.reject(Object.assign(new Error('Unsupported'), { name: 'SecurityError' }));
  }
  return navigator.mediaDevices.getUserMedia({
    video: { facingMode: { ideal: 'user' }, width: { ideal: 640 }, height: { ideal: 480 } },
    audio: false,
  });
}

export function cameraErrorMessage(error) {
  const errors = {
    NotAllowedError: 'Camera permission is blocked. Allow camera access for this site in your browser settings, then reload.',
    NotFoundError: 'No camera was found. Connect a camera and reload.',
    NotReadableError: 'The camera is busy or unavailable. Close other apps using it, then reload.',
    OverconstrainedError: 'The camera could not use the requested settings. Try another camera.',
    SecurityError: 'Camera access needs a secure connection (https or localhost) and a supported browser.',
  };
  return errors[error?.name] ?? `Camera could not start (${error?.name ?? 'unknown error'}).`;
}
