<p>A new B2B lead has been submitted.</p>
<p><strong>Reference:</strong> {{ $lead->reference ?: sprintf('INQ-%06d', $lead->id) }}</p>
<p><strong>Interest type:</strong> {{ $lead->interest_type ?? $lead->lead_type }}</p>
<p><strong>Application:</strong> {{ $lead->application_type ?? $lead->inquiry_type }}</p>
<p><strong>Contact person:</strong> {{ $lead->name }}</p>
<p><strong>Company:</strong> {{ $lead->company_name }}</p>
<p><strong>Email:</strong> {{ $lead->email }}</p>
<p><strong>Estimated quantity:</strong> {{ $lead->estimated_quantity ?? 'N/A' }}</p>
<p><strong>Source:</strong> {{ $lead->source_page ?? 'N/A' }}</p>
<p><strong>Message:</strong></p>
<p>{{ $lead->message }}</p>
