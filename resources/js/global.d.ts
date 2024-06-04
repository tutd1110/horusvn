import Echo from 'laravel-echo';

declare global {
  interface Window {
    Echo: typeof Echo & {
      private(channelName: string): any; // Replace 'any' with the appropriate type if available
    };
  }
}