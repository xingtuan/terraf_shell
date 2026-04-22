import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"
import type { SiteMessages } from "@/lib/i18n"

type StoreFaqProps = {
  content: SiteMessages["storePage"]["faq"]
}

export function StoreFaq({ content }: StoreFaqProps) {
  return (
    <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
      <div className="max-w-3xl">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">
          {content.eyebrow}
        </p>
        <h3 className="mt-3 font-serif text-3xl text-foreground">
          {content.title}
        </h3>
      </div>

      <Accordion type="single" collapsible className="mt-8">
        {content.items.map((item, index) => (
          <AccordionItem key={item.question} value={`faq-${index}`}>
            <AccordionTrigger className="text-base text-foreground hover:no-underline">
              {item.question}
            </AccordionTrigger>
            <AccordionContent className="text-sm leading-relaxed text-muted-foreground">
              {item.answer}
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
    </section>
  )
}
