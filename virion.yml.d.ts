type VirionYml = VirionSourceYml | VirionConsumerYml

type VirionSourceYml = {
	name: string
	description?: string
	authors?: string[]
	antigen: string
	version: string
	sharable?: string
} & ({php: string[]} | {api: string[]})

type VirionConsumerYml = {
	libs: {
		src: string
		version: string
		vendor?: string
		[getParameter: string]: string | number | boolean // Note: boolean is encoded as "on" or "off"!
	}
}
