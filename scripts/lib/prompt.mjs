import readline from 'node:readline/promises'

/**
 * Node's `readline/promises` only reliably supports one `.question()`
 * per interface when stdin is a non-TTY pipe (a second call never
 * resolves — the 'line' events for buffered piped data are consumed
 * eagerly before the second question attaches its listener). Piped
 * input (automation/CI, and this project's own scripted tests) needs
 * multiple sequential answers, so for that case we read all of stdin
 * once upfront and serve answers from a queue instead of calling
 * `.question()` repeatedly.
 */

let pipedLinesPromise

async function readAllPipedLines() {
  if (!pipedLinesPromise) {
    pipedLinesPromise = (async () => {
      const chunks = []
      for await (const chunk of process.stdin) {
        chunks.push(chunk)
      }
      const content = Buffer.concat(chunks.map((c) => (Buffer.isBuffer(c) ? c : Buffer.from(c))))
      return content.toString('utf8').split('\n')
    })()
  }
  return pipedLinesPromise
}

let pipedLineIndex = 0

async function nextPipedLine() {
  const lines = await readAllPipedLines()
  const line = lines[pipedLineIndex] ?? ''
  pipedLineIndex += 1
  return line.replace(/\r$/, '')
}

let ttyInterface

function getTtyInterface() {
  if (!ttyInterface) {
    ttyInterface = readline.createInterface({ input: process.stdin, output: process.stdout })
  }
  return ttyInterface
}

export async function promptText(question) {
  if (!process.stdin.isTTY) {
    process.stdout.write(question)
    const line = await nextPipedLine()
    process.stdout.write(`${line}\n`)
    return line.trim()
  }
  const answer = await getTtyInterface().question(question)
  return answer.trim()
}

/** Prompts for a value without echoing it to the terminal (real TTY only — piped input has nothing to mask). */
export async function promptHidden(question) {
  if (!process.stdin.isTTY) {
    process.stdout.write(question)
    const line = await nextPipedLine()
    process.stdout.write('\n')
    return line.trim()
  }

  return new Promise((resolve, reject) => {
    const stdin = process.stdin
    const stdout = process.stdout
    stdout.write(question)

    let value = ''
    stdin.setRawMode(true)
    stdin.resume()
    stdin.setEncoding('utf8')

    const onData = (char) => {
      switch (char) {
        case '\n':
        case '\r':
        case '\u0004':
          stdin.setRawMode(false)
          stdin.removeListener('data', onData)
          stdout.write('\n')
          resolve(value)
          break
        case '\u0003':
          stdin.setRawMode(false)
          stdin.removeListener('data', onData)
          reject(new Error('Aborted'))
          break
        case '\u007f':
        case '\b':
          value = value.slice(0, -1)
          break
        default:
          value += char
          break
      }
    }

    stdin.on('data', onData)
  })
}

export function closePrompts() {
  ttyInterface?.close()
}
