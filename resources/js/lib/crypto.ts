const textEncoder = new TextEncoder();
const textDecoder = new TextDecoder();

const toBase64 = (bytes: Uint8Array) => {
    let binary = '';
    for (let i = 0; i < bytes.length; i += 1) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
};

const fromBase64 = (value: string) => {
    const binary = atob(value);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
};

const fromText = (text: string) => textEncoder.encode(text);

export type EncryptedPayload = {
    ciphertext: string;
    iv: string;
    key?: string;
    salt?: string;
};

export const encrypt = async (plaintext: string): Promise<EncryptedPayload> => {
    const keyBytes = crypto.getRandomValues(new Uint8Array(32));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const key = await crypto.subtle.importKey('raw', keyBytes, { name: 'AES-GCM' }, false, ['encrypt', 'decrypt']);
    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, fromText(plaintext));

    return {
        ciphertext: toBase64(new Uint8Array(ciphertext)),
        iv: toBase64(iv),
        key: toBase64(keyBytes),
    };
};

export const encryptWithPassword = async (plaintext: string, password: string): Promise<EncryptedPayload> => {
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const keyMaterial = await crypto.subtle.importKey('raw', fromText(password), 'PBKDF2', false, ['deriveKey']);
    const key = await crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt,
            iterations: 250_000,
            hash: 'SHA-256',
        },
        keyMaterial,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt'],
    );

    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, fromText(plaintext));

    return {
        ciphertext: toBase64(new Uint8Array(ciphertext)),
        iv: toBase64(iv),
        salt: toBase64(salt),
    };
};

export const decrypt = async (payload: { ciphertext: string; iv: string; key: string }): Promise<string> => {
    const keyBytes = fromBase64(payload.key);
    const iv = fromBase64(payload.iv);
    const ciphertext = fromBase64(payload.ciphertext);
    const key = await crypto.subtle.importKey('raw', keyBytes, { name: 'AES-GCM' }, false, ['decrypt']);
    const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, ciphertext);
    return textDecoder.decode(decrypted);
};

export const decryptWithPassword = async (payload: { ciphertext: string; iv: string; salt: string }, password: string): Promise<string> => {
    const salt = fromBase64(payload.salt);
    const iv = fromBase64(payload.iv);
    const ciphertext = fromBase64(payload.ciphertext);
    const keyMaterial = await crypto.subtle.importKey('raw', fromText(password), 'PBKDF2', false, ['deriveKey']);
    const key = await crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt,
            iterations: 250_000,
            hash: 'SHA-256',
        },
        keyMaterial,
        { name: 'AES-GCM', length: 256 },
        false,
        ['decrypt'],
    );
    const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, ciphertext);
    return textDecoder.decode(decrypted);
};
