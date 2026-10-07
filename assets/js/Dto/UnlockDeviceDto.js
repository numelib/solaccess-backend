// @ts-check

export class UnlockDeviceDto {
    #statusUrl;

    /**
     * @param { URL } statusUrl
     */
    constructor(statusUrl) {
        this.#statusUrl = statusUrl;
    }

    get statusUrl() {
        return this.#statusUrl;
    }

    /**
     * @param { object } obj
     * @returns { UnlockDeviceDto }
     */
    static fromObject(obj) {
        if ("statusUrl" in obj === false) {
            throw new Error(
                `${UnlockDeviceDto.fromObject.name} : property "statusUrl" not found in "obj"`,
            );
        }

        if (
            typeof obj.statusUrl !== "string" &&
            !(obj.statusUrl instanceof URL)
        ) {
            throw new Error(
                `${UnlockDeviceDto.fromObject.name} : property "statusUrl" must be an instanceof ${URL.name}`,
            );
        }

        /** @type { URL } */
        let statusUrl;
        if (typeof obj.statusUrl === "string") {
            const parsed = URL.parse(obj.statusUrl);
            if (parsed === null) {
                throw new Error(
                    `${UnlockDeviceDto.fromObject.name} : property "statusUrl" cannot be parsed to a valid ${URL.name} instance`,
                );
            }

            statusUrl = parsed;
        } else {
            statusUrl = obj.statusUrl;
        }

        return new UnlockDeviceDto(statusUrl);
    }
}
