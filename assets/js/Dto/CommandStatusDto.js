// @ts-check

export class CommandStatusDto {
    #id;
    #type;
    #issuedAt;
    #processedAt;
    #timeoutAt;
    #status;

    /**
     * @param { number } id
     * @param { string } type
     * @param { number|null } issuedAt - UNIX timestamp in seconds
     * @param { number|null } processedAt - UNIX timestamp in seconds
     * @param { number|null } timeoutAt - UNIX timestamp in seconds
     * @param { 'timeout'|'processed'|'in_progress' } status
     */
    constructor(id, type, issuedAt, processedAt, timeoutAt, status) {
        this.#id = id;
        this.#type = type;
        this.#issuedAt = issuedAt;
        this.#processedAt = processedAt;
        this.#timeoutAt = timeoutAt;
        this.#status = status;
    }

    get id() {
        return this.#id;
    }

    get type() {
        return this.#type;
    }

    get issuedAt() {
        return this.#issuedAt;
    }

    get issuedAtDate() {
        return this.#issuedAt !== null ? new Date(this.#issuedAt * 1000) : null;
    }

    get processedAt() {
        return this.#processedAt;
    }

    get processedAtDate() {
        return this.#processedAt !== null
            ? new Date(this.#processedAt * 1000)
            : null;
    }

    get timeoutAt() {
        return this.#timeoutAt;
    }

    get timeoutAtDate() {
        return this.#timeoutAt !== null
            ? new Date(this.#timeoutAt * 1000)
            : null;
    }

    get status() {
        return this.#status;
    }

    /**
     * @param { { [key: string]: unknown } } obj
     * @returns { CommandStatusDto }
     */
    static fromObject(obj) {
        if ("type" in obj === false) {
            throw new Error(
                `${CommandStatusDto.name} : expected property "type" to be defined`,
            );
        }

        if (typeof obj.type !== "string") {
            throw new Error(
                `${CommandStatusDto.name} : expected property "type" to be a string`,
            );
        }

        const props = ["id", "issuedAt", "processedAt", "timeoutAt"];
        props.forEach((prop) => {
            if (prop in obj === false) {
                throw new Error(
                    `${CommandStatusDto.name} : expected property "${prop}" to be defined`,
                );
            }

            if (typeof obj[prop] !== "number" && obj[prop] !== null) {
                throw new Error(
                    `${CommandStatusDto.name} : expected property "${prop}" to be a number or null`,
                );
            }
        });

        if ("status" in obj === false) {
            throw new Error(
                `${CommandStatusDto.name} : expected property "status" to be defined`,
            );
        }

        if (typeof obj.status !== "string") {
            throw new Error(
                `${CommandStatusDto.name} : expected property "status" to be a string`,
            );
        }

        const statuses = ["timeout", "processed", "in_progress"];
        if (statuses.includes(obj.status) === false) {
            throw new Error(
                `${CommandStatusDto.name} : expected property "status" to be one of the following values ${statuses.join(", ")}`,
            );
        }

        const id = /** @type { number } */ (obj[props[0]]);
        const issuedAt = /** @type { number | null } */ (obj[props[1]]);
        const processedAt = /** @type { number | null } */ (obj[props[2]]);
        const timeoutAt = /** @type { number | null } */ (obj[props[3]]);
        const status = /** @type { 'timeout'|'processed'|'in_progress' } */ (
            obj.status
        );

        return new CommandStatusDto(
            id,
            obj.type,
            issuedAt,
            processedAt,
            timeoutAt,
            status,
        );
    }
}
