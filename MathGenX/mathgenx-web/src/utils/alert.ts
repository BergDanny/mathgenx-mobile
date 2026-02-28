import { createApp, nextTick } from 'vue';
import ConfirmAlert from '@/components/Alerts/ConfirmAlert.vue';

export function confirmAlert(
  title: string,
  message: string,
  options: { confirmText?: string; cancelText?: string } = {}
): Promise<boolean> {
  return new Promise((resolve) => {
    const container = document.createElement('div');
    document.body.appendChild(container);

    let cleaned = false;
    const cleanup = () => {
      if (cleaned) return;
      cleaned = true;
      app.unmount();
      container.remove();
    };

    const app = createApp(ConfirmAlert, {
      title,
      message,
      confirmText: options.confirmText ?? 'Confirm',
      cancelText: options.cancelText ?? 'Cancel',
      onConfirm: () => {
        resolve(true);
        cleanup();
      },
      onCancel: () => {
        resolve(false);
        cleanup();
      },
      onClosed: () => {
        resolve(false);
        cleanup();
      }
    });

    const vm = app.mount(container) as any;
    nextTick(() => vm.open?.());
  });
}