'use client';

export default function Loading() {
  return (
    <div className="bg-opacity-30 fixed inset-0 z-[9999] flex items-center justify-center bg-black">
      <div className="h-16 w-16 animate-spin rounded-full border-t-4 border-solid border-blue-500" />
    </div>
  );
}
