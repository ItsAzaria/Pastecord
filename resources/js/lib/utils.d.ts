import type { ClassValue } from 'clsx';

export declare function cn(...inputs: ClassValue[]): string;

export type WithElementRef<T> = T & { ref?: HTMLElement | null };

export type WithoutChildrenOrChild<T> = Omit<T, 'children' | 'child'>;
