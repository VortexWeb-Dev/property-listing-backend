<?xml version="1.0" encoding="UTF-8"?>
<list last_update="{{ now()->format('Y-m-d H:i:s') }}" listing_count="{{ $listings->count() }}">
@foreach($listings as $listing)
    <property last_update="{{ $listing->updated_at->format('Y-m-d H:i:s') }}">
        <reference_number><![CDATA[{{ $listing->reference_no }}]]></reference_number>
        <offering_type><![CDATA[{{ $listing->offering_type == 'sale' ? 'RS' : 'RR' }}]]></offering_type>
        <property_type><![CDATA[AP]]></property_type>
        <permit_number><![CDATA[{{ $listing->rera_permit }}]]></permit_number>
        <city><![CDATA[Dubai]]></city>
        <community><![CDATA[Business Bay]]></community>
        <sub_community><![CDATA[Tower A]]></sub_community>
        <property_name><![CDATA[{{ $listing->project_name }}]]></property_name>
        <title_en><![CDATA[{{ $listing->title }}]]></title_en>
        <description_en><![CDATA[{!! strip_tags($listing->description_english) !!}]]></description_en>
        <price>{{ $listing->rprice_price }}</price>
        <cheques>{{ $listing->no_of_cheques }}</cheques>
        <bathroom>{{ $listing->bathrooms }}</bathroom>
        <bedroom>{{ $listing->bedrooms }}</bedroom>
        <size>{{ $listing->size }}</size>
        <plot_size>{{ $listing->total_plot_size }}</plot_size>
        <furnished><![CDATA[{{ ucfirst($listing->furnished) }}]]></furnished>
        <agent>
            <id>{{ $listing->agent_id }}</id>
            <name>{{ $listing->agent->name ?? 'Agent' }}</name>
            <email>{{ $listing->agent->email ?? 'agent@example.com' }}</email>
            <phone>{{ $listing->agent->phone ?? '0000000000' }}</phone>
            <photo>{{ $listing->agent->photo_url ?? '' }}</photo>
        </agent>
        <photo>
            @foreach(json_decode($listing->photos_urls, true) ?? [] as $photo)
                <url last_updated="{{ $listing->updated_at->format('Y-m-d H:i:s') }}">
                    <![CDATA[{{ asset($photo) }}]]>
                </url>
            @endforeach
        </photo>
    </property>
@endforeach
</list>
