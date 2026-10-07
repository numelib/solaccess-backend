// @ts-check

import { isAccessDeviceLockState, lockStates } from "../validation.js";
import { CommandStatusDto } from "./CommandStatusDto.js";

export class CommandsStatusDto {
    #pageCount;
    #commands;

    /**
     * @param { CommandStatusDto[] } commands
     * @param { number } pageCount
     */
    constructor(commands, pageCount) {
        this.#commands = commands;
        this.#pageCount = pageCount;
    }

    get commands() {
        return this.#commands;
    }

    get pageCount() {
        return this.#pageCount;
    }

    /**
     * @param { object } obj
     * @returns { CommandsStatusDto }
     */
    static fromObject(obj) {
        if ("commands" in obj === false) {
            throw new Error(
                `${CommandsStatusDto.name} : expected property "commands" to be defined`,
            );
        }

        if (Array.isArray(obj.commands) === false) {
            throw new Error(
                `${CommandsStatusDto.name} : expected property "commands" to be an array`,
            );
        }

        /** @type { CommandStatusDto[] } */
        const commands = [];
        obj.commands.forEach((command) => {
            if (typeof command !== "object") {
                throw new Error(
                    `${CommandsStatusDto.name} : expected property "commands" to be an array of objects`,
                );
            }

            try {
                commands.push(CommandStatusDto.fromObject(command));
            } catch (error) {
                if (!(error instanceof Error)) throw error;
                throw new Error(
                    `${CommandsStatusDto.name} : expected property "commands" to be an array of objects that can be converted to a ${CommandStatusDto.name} instance : ${error.message}`,
                );
            }
        });

        if ("pageCount" in obj !== true || typeof obj.pageCount !== "number") {
            throw new Error(
                `${CommandsStatusDto.name} : expected property "pageCount" to be defined as a number`,
            );
        }

        return new CommandsStatusDto(commands, obj.pageCount);
    }
}
