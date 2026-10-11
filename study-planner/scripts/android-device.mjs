// Talks to an already-running local ADB server; does not start or configure it.
import net from 'node:net';
import fs from 'node:fs/promises';
import { pathToFileURL } from 'node:url';

export class AdbConnection {
    constructor() {
        this.socket = net.connect(5037, '127.0.0.1');
        this.input = Buffer.alloc(0);
        this.socket.setTimeout(60000, () => this.socket.destroy(new Error('ADB request timed out')));
        this.socket.on('data', data => {
            this.input = Buffer.concat([this.input, data]);
            this.wake?.();
        });
        this.socket.on('error', error => { this.error = error; this.wake?.(); });
        this.socket.on('end', () => { this.ended = true; this.wake?.(); });
    }

    async read(length) {
        while (this.input.length < length) {
            if (this.error) throw this.error;
            if (this.ended) throw new Error('ADB connection closed unexpectedly');
            await new Promise(resolve => { this.wake = resolve; });
        }
        this.wake = undefined;
        const result = this.input.subarray(0, length);
        this.input = this.input.subarray(length);
        return result;
    }

    async request(service) {
        this.socket.write(service.length.toString(16).padStart(4, '0') + service);
        const status = (await this.read(4)).toString();
        if (status !== 'OKAY') {
            const length = parseInt((await this.read(4)).toString(), 16);
            throw new Error((await this.read(length)).toString());
        }
    }

    async transport(serial) {
        await this.request(`host:transport:${serial}`);
    }

    async shell(serial, command) {
        await this.transport(serial);
        await this.request(`shell:${command}`);
        while (!this.ended && !this.error) await new Promise(resolve => { this.wake = resolve; });
        if (this.error) throw this.error;
        return this.input.toString();
    }

    packet(id, data) {
        const header = Buffer.alloc(8);
        header.write(id, 0, 4, 'ascii');
        header.writeUInt32LE(typeof data === 'number' ? data : data.length, 4);
        this.socket.write(header);
        if (typeof data !== 'number') this.socket.write(data);
    }

    async push(serial, localPath, remotePath) {
        const contents = await fs.readFile(localPath);
        await this.transport(serial);
        await this.request('sync:');
        this.packet('SEND', Buffer.from(`${remotePath},33188`));
        for (let offset = 0; offset < contents.length; offset += 65536) {
            this.packet('DATA', contents.subarray(offset, offset + 65536));
        }
        this.packet('DONE', Math.floor(Date.now() / 1000));
        const response = await this.read(8);
        if (response.subarray(0, 4).toString() !== 'OKAY') {
            throw new Error((await this.read(response.readUInt32LE(4))).toString());
        }
    }

    close() { this.socket.destroy(); }
}

export async function adbShell(serial, command) {
    const connection = new AdbConnection();
    try { return await connection.shell(serial, command); } finally { connection.close(); }
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
    const [operation, serial, ...args] = process.argv.slice(2);
    const connection = new AdbConnection();
    try {
        if (operation === 'devices') {
            await connection.request('host:devices-l');
            const length = parseInt((await connection.read(4)).toString(), 16);
            console.log((await connection.read(length)).toString());
        } else if (operation === 'install') {
            const remotePath = '/data/local/tmp/study-planner-updated.apk';
            await connection.push(serial, args[0], remotePath);
            connection.close();
            const result = await adbShell(serial, `pm install -r -t ${remotePath}`);
            console.log(result);
            if (!result.includes('Success')) process.exitCode = 1;
        } else if (operation === 'shell') {
            console.log(await connection.shell(serial, args.join(' ')));
        } else {
            throw new Error('Usage: node scripts/android-device.mjs devices | install SERIAL APK | shell SERIAL COMMAND');
        }
    } finally { connection.close(); }
}
