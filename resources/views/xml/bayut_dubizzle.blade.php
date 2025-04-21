<?xml version="1.0" encoding="UTF-8"?>
<Properties>
@foreach($listings as $listing)
    <Property>
        <Property_Ref_No><![CDATA[{{ $listing->reference_no }}]]></Property_Ref_No>
        <Permit_Number><![CDATA[{{ $listing->rera_permit }}]]></Permit_Number>
        <Property_Status><![CDATA[live]]></Property_Status>
        <Property_purpose><![CDATA[{{ $listing->offering_type == 'sale' ? 'Buy' : 'Rent' }}]]></Property_purpose>
        <Property_Type><![CDATA[{{ $listing->property_type ?? 'Apartment' }}]]></Property_Type>
        <Property_Size><![CDATA[{{ $listing->size }}]]></Property_Size>
        <Property_Size_Unit><![CDATA[SQFT]]></Property_Size_Unit>
        <plotArea><![CDATA[{{ $listing->total_plot_size }}]]></plotArea>
        <Bedrooms><![CDATA[{{ $listing->bedrooms }}]]></Bedrooms>
        <Bathrooms><![CDATA[{{ $listing->bathrooms }}]]></Bathrooms>
        <Furnished><![CDATA[{{ ucfirst($listing->furnished ?? 'No') }}]]></Furnished>
        <Off_plan><![CDATA[No]]></Off_plan>

        <Property_Title><![CDATA[{{ $listing->title }}]]></Property_Title>
        <Property_Title_AR><![CDATA[{{ $listing->{'title(arabic)'} }}]]></Property_Title_AR>
        <Property_Description><![CDATA[{!! strip_tags($listing->{'description(english)'} ?? '') !!}]]></Property_Description>
        <Property_Description_AR><![CDATA[{!! strip_tags($listing->{'description(arabic)'} ?? '') !!}]]></Property_Description_AR>

        <Price><![CDATA[{{ $listing->rprice_price }}]]></Price>
        <Rent_Frequency><![CDATA[{{ $listing->rental_period ?? 'yearly' }}]]></Rent_Frequency>

        <City><![CDATA[{{ $listing->location->city ?? 'Dubai' }}]]></City>
        <Locality><![CDATA[{{ $listing->location->area ?? 'Business Bay' }}]]></Locality>
        <Sub_Locality><![CDATA[{{ $listing->location->sub_area ?? '' }}]]></Sub_Locality>
        <Tower_Name><![CDATA[{{ $listing->project_name }}]]></Tower_Name>

        <Listing_Agent><![CDATA[{{ $listing->agent->name ?? 'Agent Name' }}]]></Listing_Agent>
        <Listing_Agent_Phone><![CDATA[{{ $listing->agent->phone ?? '+971000000000' }}]]></Listing_Agent_Phone>
        <Listing_Agent_Email><![CDATA[{{ $listing->agent->email ?? 'agent@example.com' }}]]></Listing_Agent_Email>

        <Portals>
            @if($listing->bayut_enable)<Portal><![CDATA[Bayut]]></Portal>@endif
            @if($listing->dubizzle_enable)<Portal><![CDATA[dubizzle]]></Portal>@endif
        </Portals>

        <Images>
            @foreach(json_decode($listing->photos_urls, true) ?? [] as $img)
                <Image><![CDATA[{{ asset($img) }}]]></Image>
            @endforeach
        </Images>

        @if($listing->video_url)
        <Videos>
            <Video><![CDATA[{{ $listing->video_url }}]]></Video>
        </Videos>
        @endif

        @if($listing->floor_plan)
        <Floor_Plans>
            <Floor_Plan><![CDATA[{{ asset($listing->floor_plan) }}]]></Floor_Plan>
        </Floor_Plans>
        @endif

        <Last_Updated><![CDATA[{{ $listing->updated_at->format('Y-m-d H:i:s') }}]]></Last_Updated>
    </Property>
@endforeach
</Properties>
