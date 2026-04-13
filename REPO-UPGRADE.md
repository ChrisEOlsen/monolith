I would like to upgrade this template repo. The goal is to automate the entire process from start to finish, leaving a intermediate human in the loop step so I can tweak the web app to look and do exactly what I want. Read the current README.md and take a look at the codebase to understand what this template repo does, then help me to find a way to stich together all of my claude plugins, skills and mcp tools to one super workflow. I think the best way to do it is to create a .claude folder so that the workflow has local scope. I should just be able to install claude code on a server, download the repo - and claude code should have all the tools it needs to complete the whole workflow. 

These are the tools that I always use, that I want the full automated workflow to explicitly make use of (calling skills and plugins on demand). 

## Plugins, SKILLS and MCP

- The php-monolith-builder custom MCP server - this is the custom MCP server which is in project scope that the LLM has access to. This MCP server should be configured in the local .claude folder within the project dir so that the LLM ALWAYS has access to this MCP server. 

- superpowers plugin - This will be the plugin that is used to actually write the code as an agentic workflow - using the MCP server mentioned above to ensure a consistent codebase using template code first, then filling in the gaps.

- graphify  - This will be done at the end of the entire workflow to create a nice mind map visual in Obsidian when all is said and done.

- https://github.com/gemini-cli-extensions/security : This is a gemini cli extension that I want to make available to claude code and have it make use of it.

- Stripe MCP server: Should be used and implemented whenever there are payments involved (which will be more of the time)

- Cloudflare MCP server: Used for configuring the cloudflare tunnel that will be used for hosting easily on a pre-purchased domain.

- OpenRouter API: This is what will be used whenever there are AI features - which will usually be involed in some way shape or form.

- Context7 MCP server: This will be used whenever there is an external API that will be used for application features in order to ensure up to date info for how to implement.

- Uncodixify skill: These are the design principles that will be used when developing the application.

- Consider the CLAUDE.md file. Currently, there is a CLAUDE.md file which outlines how the LLM should go about building the project and the design principles used to develop the application - very important that these prinicples are preserved throughout this automated workflow that we are about to create. 

## This is the workflow that I am envisioning. 

1. I write a markdown file (I want you to create a template for this that will always be present in the template repo so that I can easily fill it in and add stuff when starting a new project) called SEED.md - everything will be built from this.

1.5 The LLM should take a look at the features outlined in SEED.md and ensure that the user has added the necessary variables to the .env file. For example if the OpenRouter API is going to be used, the developer should be prompted to add the OPENROUTER_API_KEY before continuing. Same for any STRIPE secrets or Cloudflare credentials like tunnel token.

2. The superpowers plugin gets triggered, and uses its brainstorming skill to start a conversation with the developer in order to clarify any missing specifications or architectural decisions. The SEED.md will inevitably be missing some important things, so this brainstorming step should be explicitly triggered and force the developer to fill in the gaps.

3. From here, the superpowers plugin should do its thing. create the worktrees, write the specs, the writing plans etc., and then use the subagent driven workflow to implement the code - ideally without asking for permission every step of the way (one annoying aspect because literally any grep command or that sort of thing always requires permission.)
I just want the LLM to get back to me when the thing is done. Ensure that the superpowers plugin is making use of the Uncodixify skill for the frontend design stuff.

4. The first rendition of the project at this point should be pretty much complete and being hosted on localhost. But before the developer is told anything is done, the whole codebase should be analyzed for security bugs by the gemini cli security extensions collection of skills - which will produce a document of security hardening suggestions. Often times when I have run this security flow, it often pinpoints vulnerabilities around the MCP server that builds the project itself - so in order to avoid the LLM making any changes to the actual template code (the jinja templates) or the MCP server itself, the LLM should be told that the MCP server will be deactivated in production, so the only things that we are really looking for are the typical XSS or SQL injection type vulnerabilites - the key takeaway is that the MCP server php-monolith-builder will be deactivated in production, so the LLM should just exclude it from the attack surface.

5. Once the document of security suggestions is created, the LLM should use the superpowers plugin again to implement the fixes, but this time the developer does not need to be involved in a brainstorming step. Just fix the security issues that are especially of critical or medium importance.

6. Once the superpowers plugin has done its thing the second time, and the code has been implemented and tested - the developer can be informed that the application is done and running on localhost at a specified port (all of those things will be in the .env file)

7. Once the developer has approved of the application, the developer should then explicitly call a SKILL.md file called /launch, which makes use of the cloudflare mcp server to create a tunnel, connect the specified domain to it and rebuild the docker containers. Together with this step I want the graphify plugin to automatically be triggered so that the whole obsidian graph is created.
